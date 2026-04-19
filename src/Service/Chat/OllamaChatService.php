<?php

declare(strict_types=1);

namespace App\Service\Chat;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Entity\Suivihabitude;

final class OllamaChatService
{
    public function __construct(
        private readonly string $apiUrl,
        private readonly string $model,
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
        $payload = [
            'model' => $this->model,
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
            ],
        ];

        $response = $this->request($payload);
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
        ];
    }

    public function generateGenericReply(string $message): ?string
    {
        $payload = [
            'model' => $this->model,
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
            ],
        ];

        $response = $this->request($payload);
        $reply = trim((string) ($response['message']['content'] ?? ''));

        return $reply !== '' ? $reply : null;
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
Tu es MindBot, l'assistant de l'application MindTrack.
Tu aides l'utilisateur a suivre ses habitudes avec des reponses claires, utiles et motivantes.
Reponds toujours en francais.
Reste concis: 3 phrases maximum, sauf si une liste courte est vraiment utile.
Base-toi uniquement sur le contexte fourni et n'invente pas de statistiques.
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
                '- %s | type: %s | objectif: %s | completion: %s%% | risque: %s | serie actuelle: %d',
                $habitude->getNom(),
                $habitude->getHabitType() ?? 'inconnu',
                $habitude->getObjectif() ?? 'non precise',
                number_format($completion, 0, ',', ' '),
                $risk,
                $streak
            );
        }, $habitudes);

        $reminderLines = array_map(
            static fn (Rappel_habitude $rappel): string => sprintf(
                '- %s a %s (%s)',
                $rappel->getIdHabitude()?->getNom() ?? 'Habitude',
                $rappel->getHeureRappel() ?? 'heure inconnue',
                $rappel->getJours() ?: 'jours non precises'
            ),
            $rappels
        );

        $fallbackSection = $fallbackReply;
        if ($fallbackHighlights !== []) {
            $fallbackSection .= "\nPoints utiles: " . implode(' | ', $fallbackHighlights);
        }

        return <<<PROMPT
Question utilisateur: {$message}

Contexte MindTrack:
- Habitudes actives: {$this->countItems($habitudes)}
- Rappels actifs: {$this->countItems($rappels)}
- Suivis completes aujourd hui: {$completedToday}

Habitudes:
{$this->joinLines($habitLines, '- Aucune habitude')}

Rappels:
{$this->joinLines($reminderLines, '- Aucun rappel actif')}

Si le contexte ne suffit pas, reste honnete et propose une action simple.
Tu peux t'inspirer de cette reponse locale de secours, mais ta reponse finale doit etre naturelle:
{$fallbackSection}
PROMPT;
    }

    private function request(array $payload): ?array
    {
        $content = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($content === false) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $content,
                'timeout' => 45,
                'ignore_errors' => true,
            ],
        ]);

        $rawResponse = @file_get_contents($this->apiUrl, false, $context);
        if ($rawResponse === false) {
            return null;
        }

        $statusCode = $this->extractStatusCode($http_response_header ?? []);
        if ($statusCode < 200 || $statusCode >= 300) {
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
}
