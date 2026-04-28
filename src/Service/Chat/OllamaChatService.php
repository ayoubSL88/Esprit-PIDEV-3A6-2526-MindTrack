<?php

declare(strict_types=1);

namespace App\Service\Chat;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Entity\Suivihabitude;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OllamaChatService
{
    private const CHAT_TIMEOUT_SECONDS = 25;
    private const TAGS_TIMEOUT_SECONDS = 5;
    private const MAX_HABITS_IN_PROMPT = 6;
    private const MAX_REMINDERS_IN_PROMPT = 4;

    public function __construct(
        private readonly ?string $apiUrl,
        private readonly ?string $model,
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param list<Habitude> $habitudes
     * @param list<Rappel_habitude> $rappels
     * @param list<Suivihabitude> $todaySuivis
     * @param array<int, array<string, mixed>> $advancedInsights
     * @param list<string> $fallbackHighlights
     */
    public function generateHabitReply(
        string $message,
        array $habitudes,
        array $rappels,
        array $todaySuivis,
        array $advancedInsights,
        string $fallbackReply,
        array $fallbackHighlights = [],
    ): ?array {
        if ($this->isDisabled()) {
            return null;
        }

        $model = $this->resolveModel();
        if ($model === null) {
            return null;
        }

        $payload = [
            'model' => $model,
            'stream' => false,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt($message, $habitudes, $rappels, $todaySuivis, $advancedInsights, $fallbackReply, $fallbackHighlights),
                ],
            ],
            'options' => [
                'temperature' => 0.4,
                'num_predict' => 180,
            ],
        ];

        $response = $this->requestWithModelFallback($payload);
        if ($response === null) {
            return null;
        }

        $reply = trim((string) ($response['message']['content'] ?? ''));
        if ($reply === '') {
            return null;
        }

        return [
            'reply' => $reply,
            'highlights' => [],
            'source' => 'ollama',
            'model' => (string) ($response['_mindtrack_model'] ?? $model),
        ];
    }

    public function generateGenericReply(string $message): ?string
    {
        if ($this->isDisabled()) {
            return null;
        }

        $model = $this->resolveModel();
        if ($model === null) {
            return null;
        }

        $payload = [
            'model' => $model,
            'stream' => false,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
            'options' => [
                'temperature' => 0.4,
                'num_predict' => 120,
            ],
        ];

        $response = $this->requestWithModelFallback($payload);
        $reply = trim((string) ($response['message']['content'] ?? ''));

        return $reply !== '' ? $reply : null;
    }

    public function getStatus(): array
    {
        $configuredUrl = $this->resolveChatUrl();
        $resolvedModel = $this->resolveModel();
        $installedModels = $this->fetchInstalledModels();

        return [
            'enabled' => $configuredUrl !== null,
            'online' => $installedModels !== [],
            'configuredUrl' => $configuredUrl,
            'configuredModel' => $this->model !== null ? trim($this->model) : '',
            'resolvedModel' => $resolvedModel,
            'installedModels' => $installedModels,
        ];
    }

    private function requestWithModelFallback(array $payload): ?array
    {
        $response = $this->request($payload);
        if ($response !== null) {
            return $response;
        }

        // If the configured model isn't present locally, retry once with the first installed model.
        $fallbackModel = $this->discoverModelFromTags();
        if ($fallbackModel === null || ($payload['model'] ?? null) === $fallbackModel) {
            return null;
        }

        $payload['model'] = $fallbackModel;
        $fallbackResponse = $this->request($payload);

        if ($fallbackResponse !== null) {
            $fallbackResponse['_mindtrack_model'] = $fallbackModel;
        }

        return $fallbackResponse;
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Tu es MindBot, l'assistant de l'application MindTrack.
Tu aides l'utilisateur a suivre ses habitudes avec des reponses claires, utiles et motivantes.
Reponds toujours en francais.
Reste concis: 3 phrases maximum, sauf si une liste courte est vraiment utile.
Base-toi uniquement sur le contexte fourni et n'invente pas de statistiques.
Si la question est simple, reponds directement sans reformuler tout le contexte.
PROMPT;
    }

    /**
     * @param list<Habitude> $habitudes
     * @param list<Rappel_habitude> $rappels
     * @param list<Suivihabitude> $todaySuivis
     * @param array<int, array<string, mixed>> $advancedInsights
     * @param list<string> $fallbackHighlights
     */
    private function buildUserPrompt(
        string $message,
        array $habitudes,
        array $rappels,
        array $todaySuivis,
        array $advancedInsights,
        string $fallbackReply,
        array $fallbackHighlights,
    ): string {
        $completedToday = count(array_filter(
            $todaySuivis,
            static fn (Suivihabitude $suivi): bool => (bool) $suivi->getEtat()
        ));

        $habitLines = array_map(function (Habitude $habitude) use ($advancedInsights): string {
            $insight = $advancedInsights[$habitude->getIdHabitude()] ?? [];
            $risk = strtolower((string) ($insight['risk']['riskLevel'] ?? 'inconnu'));
            $completion = (float) ($insight['progress']['completionRate'] ?? 0);
            $streak = (int) ($insight['streak']['currentStreak'] ?? 0);

            return sprintf(
                '- %s | %s%% | risque %s | serie %d',
                $habitude->getNom(),
                number_format($completion, 0, ',', ' '),
                $risk,
                $streak
            );
        }, array_slice($habitudes, 0, self::MAX_HABITS_IN_PROMPT));

        $reminderLines = array_map(
            static fn (Rappel_habitude $rappel): string => sprintf(
                '- %s a %s (%s)',
                $rappel->getIdHabitude()?->getNom() ?? 'Habitude',
                $rappel->getHeureRappel() ?? 'heure inconnue',
                $rappel->getJours() ?: 'jours non precises'
            ),
            array_slice($rappels, 0, self::MAX_REMINDERS_IN_PROMPT)
        );

        $fallbackSection = $fallbackReply;
        if ($fallbackHighlights !== []) {
            $fallbackSection .= "\nPoints utiles: " . implode(' | ', $fallbackHighlights);
        }

        return <<<PROMPT
Question: {$message}

Resume:
- habitudes: {$this->countItems($habitudes)}
- rappels: {$this->countItems($rappels)}
- suivis completes aujourd hui: {$completedToday}

Habitudes:
{$this->joinLines($habitLines, '- Aucune habitude')}

Rappels:
{$this->joinLines($reminderLines, '- Aucun rappel actif')}

Si le contexte ne suffit pas, reste honnete et propose une action simple.
Reponse locale de secours disponible si besoin:
{$fallbackSection}
PROMPT;
    }

    private function request(array $payload): ?array
    {
        if ($this->isDisabled()) {
            return null;
        }

        $url = $this->resolveChatUrl();
        if ($url === null) {
            return null;
        }

        $transportError = null;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => self::CHAT_TIMEOUT_SECONDS,
            ]);
            $statusCode = $response->getStatusCode();
            $rawResponse = $response->getContent(false);
        } catch (ExceptionInterface|\JsonException|\RuntimeException $exception) {
            $transportError = $exception->getMessage();
            [$statusCode, $rawResponse, $nativeError] = $this->nativeJsonRequest('POST', $url, $payload, self::CHAT_TIMEOUT_SECONDS);
            if ($rawResponse === null) {
                $this->logger?->warning('Ollama request failed (no response).', [
                    'url' => $url,
                    'model' => (string) ($payload['model'] ?? ''),
                    'error' => $transportError,
                    'native_error' => $nativeError,
                ]);

                return null;
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logger?->warning('Ollama request failed (non-2xx).', [
                'url' => $url,
                'status' => $statusCode,
                'model' => (string) ($payload['model'] ?? ''),
                'body_preview' => mb_substr((string) $rawResponse, 0, 200),
                'transport_error' => $transportError,
            ]);
            return null;
        }

        $decoded = json_decode($rawResponse, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param list<string> $headers
     */
    private function extractStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    private function isDisabled(): bool
    {
        return $this->resolveChatUrl() === null;
    }

    private function resolveChatUrl(): ?string
    {
        $raw = $this->apiUrl !== null ? trim($this->apiUrl) : '';
        if ($raw === '') {
            return null;
        }

        // Accept full URL (`http://127.0.0.1:11434/api/chat`) or base URL (`http://127.0.0.1:11434`).
        if (str_contains($raw, '/api/')) {
            return $raw;
        }

        return rtrim($raw, '/') . '/api/chat';
    }

    private function resolveModel(): ?string
    {
        $model = $this->model !== null ? trim($this->model) : '';
        if ($model !== '') {
            return $model;
        }

        return $this->discoverModelFromTags();
    }

    private function discoverModelFromTags(): ?string
    {
        $models = $this->fetchInstalledModels();

        return $models[0] ?? null;
    }

    /**
     * @param array<int, mixed> $items
     */
    private function countItems(array $items): int
    {
        return count($items);
    }

    /**
     * @param list<string> $lines
     */
    private function joinLines(array $lines, string $fallback): string
    {
        return $lines !== [] ? implode("\n", $lines) : $fallback;
    }

    /**
     * @return list<string>
     */
    private function fetchInstalledModels(): array
    {
        $tagsUrl = $this->resolveTagsUrl();
        if ($tagsUrl === null) {
            return [];
        }

        $transportError = null;

        try {
            $response = $this->httpClient->request('GET', $tagsUrl, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => self::TAGS_TIMEOUT_SECONDS,
            ]);
            $statusCode = $response->getStatusCode();
            $rawResponse = $response->getContent(false);
        } catch (ExceptionInterface|\RuntimeException $exception) {
            $transportError = $exception->getMessage();
            [$statusCode, $rawResponse, $nativeError] = $this->nativeJsonRequest('GET', $tagsUrl, null, self::TAGS_TIMEOUT_SECONDS);
            if ($rawResponse === null) {
                $this->logger?->warning('Ollama tags request failed.', [
                    'url' => $tagsUrl,
                    'error' => $transportError,
                    'native_error' => $nativeError,
                ]);

                return [];
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            return [];
        }

        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            return [];
        }

        $models = $decoded['models'] ?? null;
        if (!is_array($models)) {
            return [];
        }

        $names = [];
        foreach ($models as $model) {
            $name = is_array($model) ? ($model['name'] ?? null) : null;
            if (is_string($name) && trim($name) !== '') {
                $names[] = trim($name);
            }
        }

        return array_values(array_unique($names));
    }

    private function resolveTagsUrl(): ?string
    {
        $chatUrl = $this->resolveChatUrl();
        if ($chatUrl === null) {
            return null;
        }

        $apiPos = strpos($chatUrl, '/api/');
        $baseUrl = $apiPos !== false ? substr($chatUrl, 0, $apiPos) : rtrim($chatUrl, '/');

        return rtrim($baseUrl, '/') . '/api/tags';
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{0: int, 1: ?string, 2: ?string}
     */
    private function nativeJsonRequest(string $method, string $url, ?array $payload, int $timeout): array
    {
        if (function_exists('curl_init')) {
            $curlResult = $this->nativeCurlJsonRequest($method, $url, $payload, $timeout);
            if ($curlResult[1] !== null) {
                return $curlResult;
            }
        }

        return $this->nativeStreamJsonRequest($method, $url, $payload, $timeout);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{0: int, 1: ?string, 2: ?string}
     */
    private function nativeCurlJsonRequest(string $method, string $url, ?array $payload, int $timeout): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            return [0, null, 'curl_init failed'];
        }

        $headers = ['Accept: application/json'];
        $content = null;

        if ($payload !== null) {
            $content = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($content === false) {
                curl_close($curl);

                return [0, null, 'json_encode failed'];
            }

            $headers[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $body = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_errno($curl) !== 0 ? curl_error($curl) : null;
        curl_close($curl);

        return [$statusCode, is_string($body) ? $body : null, $error];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{0: int, 1: ?string, 2: ?string}
     */
    private function nativeStreamJsonRequest(string $method, string $url, ?array $payload, int $timeout): array
    {
        $headers = [
            'Accept: application/json',
        ];

        $content = null;
        if ($payload !== null) {
            $content = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($content === false) {
                return [0, null, 'json_encode failed'];
            }

            $headers[] = 'Content-Type: application/json';
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $content,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            $error = error_get_last();

            return [0, null, is_array($error) ? (string) ($error['message'] ?? 'stream request failed') : 'stream request failed'];
        }

        return [$this->extractStatusCode($http_response_header ?? []), $body, null];
    }
}
