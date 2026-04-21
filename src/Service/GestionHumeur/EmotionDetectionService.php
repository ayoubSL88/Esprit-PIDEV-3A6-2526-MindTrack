<?php

namespace App\Service\GestionHumeur;

final class EmotionDetectionService
{
    /**
     * @return array{
     *     type: string,
     *     label: string,
     *     intensity: int,
     *     confidence: float,
     *     summary: string,
     *     metrics: array<string, float|int>,
     *     framesAnalyzed: int
     * }
     */
    public function detectFromDataUri(string $dataUri): array
    {
        return $this->detectFromDataUris([$dataUri]);
    }

    /**
     * @param string[] $dataUris
     * @return array{
     *     type: string,
     *     label: string,
     *     intensity: int,
     *     confidence: float,
     *     summary: string,
     *     metrics: array<string, float|int>,
     *     framesAnalyzed: int
     * }
     */
    public function detectFromDataUris(array $dataUris): array
    {
        if ($dataUris === []) {
            throw new \RuntimeException('No camera frames were captured.');
        }

        $imagePaths = [];
        try {
            foreach ($dataUris as $dataUri) {
                if (!is_string($dataUri) || trim($dataUri) === '') {
                    continue;
                }

                $imagePaths[] = $this->storeCameraCapture($dataUri);
            }

            if ($imagePaths === []) {
                throw new \RuntimeException('No valid camera frames were provided.');
            }

            return $this->aggregateDetections($imagePaths);
        } finally {
            foreach ($imagePaths as $imagePath) {
                if (is_file($imagePath)) {
                    @unlink($imagePath);
                }
            }
        }
    }

    private function storeCameraCapture(string $dataUri): string
    {
        if (!preg_match('/^data:image\/(?P<ext>png|jpeg|jpg);base64,(?P<data>.+)$/', $dataUri, $matches)) {
            throw new \RuntimeException('The captured image format is invalid.');
        }

        $binary = base64_decode(str_replace(' ', '+', (string) $matches['data']), true);
        if ($binary === false) {
            throw new \RuntimeException('The captured image could not be decoded.');
        }

        if (strlen($binary) > 8 * 1024 * 1024) {
            throw new \RuntimeException('The captured image is too large.');
        }

        $directory = $this->getWorkingDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('The detector workspace could not be created.');
        }

        $extension = strtolower((string) $matches['ext']) === 'jpg' ? 'jpeg' : strtolower((string) $matches['ext']);
        $path = $directory.'/capture_'.bin2hex(random_bytes(8)).'.'.$extension;

        if (file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('The captured image could not be stored for analysis.');
        }

        return $path;
    }

    /**
     * @return array{
     *     type: string,
     *     label: string,
     *     intensity: int,
     *     confidence: float,
     *     summary: string,
     *     metrics: array<string, float|int>,
     *     framesAnalyzed: int
     * }
     */
    private function runPythonDetector(string $imagePath): array
    {
        $scriptPath = $this->getDetectorScriptPath();
        if (!is_file($scriptPath)) {
            throw new \RuntimeException('The Python detector script is missing.');
        }

        $pythonBinary = $this->getPythonBinary();
        $command = [$pythonBinary, $scriptPath, $imagePath];
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $this->getProjectDir(), $this->getDetectorEnvironment());
        if (!is_resource($process)) {
            throw new \RuntimeException('The Python detector could not be started.');
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $payload = json_decode(trim((string) $stdout), true);
        if (!is_array($payload)) {
            $message = trim((string) $stderr) !== '' ? trim((string) $stderr) : 'The detector returned an unreadable response.';
            throw new \RuntimeException($message);
        }

        if ($exitCode !== 0) {
            $message = isset($payload['error']) && is_string($payload['error'])
                ? $payload['error']
                : (trim((string) $stderr) !== '' ? trim((string) $stderr) : 'The detector could not analyze the image.');

            throw new \RuntimeException($message);
        }

        $type = (string) ($payload['type'] ?? '');
        $allowedTypes = ['sad', 'anxious', 'happy', 'neutural', 'tired'];
        if (!in_array($type, $allowedTypes, true)) {
            throw new \RuntimeException('The detector returned an unsupported mood type.');
        }

        $intensity = max(1, min(10, (int) ($payload['intensity'] ?? 5)));
        $confidence = round(max(0.0, min(1.0, (float) ($payload['confidence'] ?? 0.5))), 2);
        $summary = trim((string) ($payload['summary'] ?? 'Mood detected from the latest camera capture.'));
        $metrics = isset($payload['metrics']) && is_array($payload['metrics']) ? $payload['metrics'] : [];

        return [
            'type' => $type,
            'label' => $this->buildLabel($type),
            'intensity' => $intensity,
            'confidence' => $confidence,
            'summary' => $summary,
            'metrics' => $metrics,
            'framesAnalyzed' => 1,
        ];
    }

    /**
     * @param string[] $imagePaths
     * @return array{
     *     type: string,
     *     label: string,
     *     intensity: int,
     *     confidence: float,
     *     summary: string,
     *     metrics: array<string, float|int>,
     *     framesAnalyzed: int
     * }
     */
    private function aggregateDetections(array $imagePaths): array
    {
        $successfulDetections = [];
        $lastRecoverableError = null;

        foreach ($imagePaths as $imagePath) {
            try {
                $successfulDetections[] = $this->runPythonDetector($imagePath);
            } catch (\Throwable $exception) {
                $lastRecoverableError = $this->buildDetectorFailureMessage($exception);
            }
        }

        if ($successfulDetections === []) {
            throw new \RuntimeException($lastRecoverableError ?? 'The detector could not analyze the captured frames.');
        }

        $groupedByType = [];
        foreach ($successfulDetections as $detection) {
            $type = $detection['type'];
            $metrics = is_array($detection['metrics'] ?? null) ? $detection['metrics'] : [];
            $dominantRaw = max(0.0, min(1.0, (float) ($metrics['dominant_emotion_raw'] ?? $detection['confidence'])));
            $weightedVote = max(0.05, ((float) $detection['confidence'] * 0.7) + ($dominantRaw * 0.3));

            if (!isset($groupedByType[$type])) {
                $groupedByType[$type] = [
                    'frames' => [],
                    'score' => 0.0,
                    'voteWeight' => 0.0,
                ];
            }

            $groupedByType[$type]['frames'][] = $detection;
            $groupedByType[$type]['score'] += (float) $detection['confidence'];
            $groupedByType[$type]['voteWeight'] += $weightedVote;
        }

        uasort($groupedByType, static function (array $left, array $right): int {
            $voteWeightComparison = $right['voteWeight'] <=> $left['voteWeight'];
            if ($voteWeightComparison !== 0) {
                return $voteWeightComparison;
            }

            $leftFrameCount = count($left['frames']);
            $rightFrameCount = count($right['frames']);

            if ($leftFrameCount !== $rightFrameCount) {
                return $rightFrameCount <=> $leftFrameCount;
            }

            return $right['score'] <=> $left['score'];
        });

        $winningGroup = reset($groupedByType);
        if ($winningGroup === false) {
            throw new \RuntimeException('The detector could not stabilize a mood result.');
        }

        $winningFrames = $winningGroup['frames'];
        $confidenceTotal = 0.0;
        $weightedIntensityTotal = 0.0;
        $averagedMetrics = [];
        $metricsCount = [];

        foreach ($winningFrames as $frame) {
            $weight = max(0.05, (float) $frame['confidence']);
            $confidenceTotal += (float) $frame['confidence'];
            $weightedIntensityTotal += ((float) $frame['intensity']) * $weight;

            foreach ($frame['metrics'] as $metric => $value) {
                if (!is_int($value) && !is_float($value)) {
                    continue;
                }

                $averagedMetrics[$metric] = ($averagedMetrics[$metric] ?? 0.0) + (float) $value;
                $metricsCount[$metric] = ($metricsCount[$metric] ?? 0) + 1;
            }
        }

        foreach ($averagedMetrics as $metric => $value) {
            $divisor = max(1, $metricsCount[$metric] ?? 1);
            $averagedMetrics[$metric] = round($value / $divisor, 4);
        }

        $sampleCount = count($winningFrames);
        $framesAnalyzed = count($successfulDetections);
        $winningType = (string) $winningFrames[0]['type'];
        $averageConfidence = round($confidenceTotal / max(1, $sampleCount), 2);
        $stabilityBoost = $sampleCount >= 3 ? 0.08 : ($sampleCount === 2 ? 0.04 : 0.0);
        $confidence = round(min(0.98, $averageConfidence + $stabilityBoost), 2);
        $averageIntensity = (int) round($weightedIntensityTotal / max(0.05, array_sum(array_map(
            static fn (array $frame): float => max(0.05, (float) $frame['confidence']),
            $winningFrames
        ))));

        return [
            'type' => $winningType,
            'label' => $this->buildLabel($winningType),
            'intensity' => max(1, min(10, $averageIntensity)),
            'confidence' => $confidence,
            'summary' => sprintf(
                '%s Confirmed by %d of %d usable frame%s in the short capture burst.',
                (string) $winningFrames[0]['summary'],
                $sampleCount,
                $framesAnalyzed,
                $framesAnalyzed === 1 ? '' : 's'
            ),
            'metrics' => $averagedMetrics,
            'framesAnalyzed' => $framesAnalyzed,
        ];
    }

    private function buildLabel(string $type): string
    {
        return match ($type) {
            'anxious' => 'Stressed',
            'happy' => 'Happy',
            'sad' => 'Sad',
            'tired' => 'Tired',
            default => 'Neutral',
        };
    }

    private function buildDetectorFailureMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());

        return $message !== ''
            ? $message
            : 'The detector could not analyze the captured frames.';
    }

    private function getDetectorScriptPath(): string
    {
        return __DIR__.'/Python/emotion_detector.py';
    }

    private function getWorkingDirectory(): string
    {
        return $this->getProjectDir().'/var/gestion_humeur/emotion_detector';
    }

    private function getPythonBinary(): string
    {
        $configuredBinary = getenv('PYTHON_BIN');
        if (is_string($configuredBinary) && trim($configuredBinary) !== '') {
            return $configuredBinary;
        }

        $venvBinary = $this->getProjectDir().'/.venv311/Scripts/python.exe';
        if (is_file($venvBinary)) {
            return $venvBinary;
        }

        return 'python';
    }

    /**
     * @return array<string, string>
     */
    private function getDetectorEnvironment(): array
    {
        $environment = $_ENV;
        foreach ($_SERVER as $key => $value) {
            if (is_string($value)) {
                $environment[$key] = $value;
            }
        }

        $environment['PYTHONIOENCODING'] = 'utf-8';

        return $environment;
    }

    private function getProjectDir(): string
    {
        return dirname(__DIR__, 3);
    }
}
