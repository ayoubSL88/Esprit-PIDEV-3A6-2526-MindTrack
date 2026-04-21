<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class QuoteController
{
    private const FALLBACK_QUOTE = [
        'q' => 'La discipline est le pont entre les objectifs et les accomplissements.',
        'a' => 'Jim Rohn',
    ];

    #[Route('/api/quotes/random', name: 'api_quotes_random', methods: ['GET'])]
    public function random(): JsonResponse
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: MindTrack/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        try {
            $response = @file_get_contents('https://zenquotes.io/api/random', false, $context);

            if ($response === false) {
                return new JsonResponse(self::FALLBACK_QUOTE);
            }

            $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $quote = is_array($payload) && isset($payload[0]) && is_array($payload[0]) ? $payload[0] : null;

            if (!is_array($quote) || empty($quote['q']) || empty($quote['a'])) {
                return new JsonResponse(self::FALLBACK_QUOTE);
            }

            return new JsonResponse([
                'q' => (string) $quote['q'],
                'a' => (string) $quote['a'],
            ]);
        } catch (\Throwable) {
            return new JsonResponse(self::FALLBACK_QUOTE);
        }
    }
}
