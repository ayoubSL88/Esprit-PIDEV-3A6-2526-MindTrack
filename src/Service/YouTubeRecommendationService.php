<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;

class YouTubeRecommendationService
{
    private string $pythonScriptPath;
    private string $pythonPath;
    private LoggerInterface $logger;

    // ✅ Plus d'apiKey dans le constructeur
    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->pythonScriptPath = $projectDir . '/python/youtube_recommender.py';
        //$this->pythonScriptPath = $projectDir . '/python/test_simple.py';
        $this->pythonPath = 'C:\\Users\\graci\\MindTrack\\venv\\Scripts\\python.exe';
    }

    private function executePython(string $action, array $params = []): array
    {
        $this->logger->info('=== DÉBUT EXÉCUTION PYTHON ===');
        $this->logger->info('Action: ' . $action);
        $this->logger->info('Script Python: ' . $this->pythonScriptPath);
        $this->logger->info('Script existe: ' . (file_exists($this->pythonScriptPath) ? 'OUI' : 'NON'));
        
        if (!file_exists($this->pythonScriptPath)) {
            $this->logger->error('Script Python introuvable: ' . $this->pythonScriptPath);
            return ['status' => 'error', 'message' => 'Script Python introuvable'];
        }

        // ✅ Ne plus passer l'apiKey (elle est dans le script Python)
        $command = [
            $this->pythonPath,
            $this->pythonScriptPath,
            $action,
            ...$params
        ];
        
        $this->logger->info('Commande: ' . implode(' ', $command));
        
        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        
        $this->logger->info('STDOUT: ' . $output);
        if (!empty($errorOutput)) {
            $this->logger->info('STDERR: ' . $errorOutput);
        }

        if (!$process->isSuccessful()) {
            $this->logger->error('Erreur code: ' . $process->getExitCode());
            return ['status' => 'error', 'message' => $errorOutput];
        }

        $result = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Erreur JSON: ' . json_last_error_msg());
            return ['status' => 'error', 'message' => 'Erreur de décodage JSON'];
        }
        
        $this->logger->info('=== FIN EXÉCUTION PYTHON ===');
        
        return $result ?? [];
    }

    public function fetchVideos(string $query = "développement personnel", int $maxResults = 30): array
    {
        return $this->executePython('fetch', [$query, (string)$maxResults]);
    }

    public function recommendByText(string $text, int $topN = 6): array
    {
        $result = $this->executePython('recommend_by_text', [$text]);
        
        if (isset($result['status']) && $result['status'] === 'error') {
            $this->logger->error('Erreur recommendByText: ' . json_encode($result));
            return [];
        }
        
        return array_slice($result, 0, $topN);
    }

    public function getRandomRecommendations(int $topN = 6): array
    {
        $this->logger->info('=== getRandomRecommendations START ===');
    
        $result = $this->executePython('random', []);
        
        $this->logger->info('Résultat brut: ' . json_encode($result));
        
        if (isset($result['status']) && $result['status'] === 'error') {
            $this->logger->error('Erreur: ' . json_encode($result));
            return [];
        }
        
        if (empty($result)) {
            $this->logger->warning('Résultat vide');
            return [];
        }
        
        $this->logger->info('Nombre de résultats: ' . count($result));
        
        return array_slice($result, 0, $topN);
    }
}