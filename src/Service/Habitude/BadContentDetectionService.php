<?php

declare(strict_types=1);

namespace App\Service\Habitude;

final class BadContentDetectionService
{
    private const DEFAULT_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const DEFAULT_MODEL = 'nvidia/nemotron-3-nano-30b-a3b:free';

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
        private readonly ?string $apiUrl = null,
        private readonly ?string $siteUrl = null,
        private readonly ?string $siteName = null,
    ) {
    }

    /**
     * @return array{blocked: bool, categories: list<string>, reason: string}
     */
    public function analyze(string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'blocked' => false,
                'categories' => [],
                'reason' => '',
            ];
        }

        $heuristicResult = $this->heuristicFallback($message);
        if ($this->apiKey === null || trim($this->apiKey) === '') {
            return $heuristicResult;
        }

        $payload = [
            'model' => $this->model ?: self::DEFAULT_MODEL,
            'temperature' => 0,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a content safety classifier. Return only minified JSON with keys blocked (boolean), categories (array of strings), and reason (string). Block only harassment, hate, sexual content involving minors, self-harm encouragement, explicit violent threats, illegal dangerous instructions, or severe abuse. Allow normal wellness, habit tracking, frustration, and neutral conversation.',
                ],
                [
                    'role' => 'user',
                    'content' => sprintf('Classify this French message: %s', $message),
                ],
            ],
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . trim($this->apiKey),
            'Accept: application/json',
        ];

        if ($this->siteUrl !== null && trim($this->siteUrl) !== '') {
            $headers[] = 'HTTP-Referer: ' . trim($this->siteUrl);
        }

        if ($this->siteName !== null && trim($this->siteName) !== '') {
            $headers[] = 'X-Title: ' . trim($this->siteName);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $rawResponse = @file_get_contents($this->apiUrl ?: self::DEFAULT_API_URL, false, $context);
        if (!is_string($rawResponse) || $rawResponse === '') {
            return $heuristicResult;
        }

        $decoded = json_decode($rawResponse, true);
        if (!is_array($decoded)) {
            return $heuristicResult;
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || trim($content) === '') {
            return $heuristicResult;
        }

        $content = trim($content);
        $jsonStart = strpos($content, '{');
        $jsonEnd = strrpos($content, '}');
        if ($jsonStart === false || $jsonEnd === false || $jsonEnd < $jsonStart) {
            return $heuristicResult;
        }

        $parsed = json_decode(substr($content, $jsonStart, ($jsonEnd - $jsonStart) + 1), true);
        if (!is_array($parsed)) {
            return $heuristicResult;
        }

        $categories = array_values(array_filter(array_map('strval', (array) ($parsed['categories'] ?? []))));

        return [
            'blocked' => (bool) ($parsed['blocked'] ?? false),
            'categories' => $categories,
            'reason' => trim((string) ($parsed['reason'] ?? '')),
        ];
    }

    /**
     * @param array<string, string|null> $fields
     * @return array{blocked: bool, field: string, label: string, categories: list<string>, reason: string}
     */
    public function analyzeFields(array $fields): array
    {
        foreach ($fields as $label => $value) {
            $result = $this->analyze((string) $value);
            if ($result['blocked']) {
                return [
                    'blocked' => true,
                    'field' => $this->normalizeFieldName($label),
                    'label' => $label,
                    'categories' => $result['categories'],
                    'reason' => $result['reason'],
                ];
            }
        }

        return [
            'blocked' => false,
            'field' => '',
            'label' => '',
            'categories' => [],
            'reason' => '',
        ];
    }

    /**
     * @return array{blocked: bool, categories: list<string>, reason: string}
     */
    private function heuristicFallback(string $message): array
    {
        $normalized = $this->normalize($message);
        $blockedPatterns = [
            'hate' => ['sale race', 'sale noir', 'sale arabe', 'sale juif', 'sale musulman', 'sale gay'],
            'self-harm' => ['je veux me suicider', 'comment me suicider', 'se tuer', 'me faire du mal'],
            'violence' => ['comment tuer', 'je vais te tuer', 'fabriquer une bombe'],
            'sexual' => ['contenu pedophile', 'mineur sexuel'],
        ];

        $categories = [];

        foreach ($blockedPatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    $categories[] = $category;
                    break;
                }
            }
        }

        return [
            'blocked' => $categories !== [],
            'categories' => $categories,
            'reason' => $categories !== [] ? 'Message potentiellement dangereux detecte par le filtre de securite.' : '',
        ];
    }

    private function normalize(string $value): string
    {
        $normalized = trim(mb_strtolower($value));
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        return $converted !== false ? $converted : $normalized;
    }

    private function normalizeFieldName(string $label): string
    {
        $normalized = $this->normalize($label);

        return str_replace(' ', '_', $normalized);
    }
}
