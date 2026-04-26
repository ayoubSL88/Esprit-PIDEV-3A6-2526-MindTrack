<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AIPlanificateurService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function generateCompletePlan(string $titre, string $description): array
    {
        // Construction du prompt pour Gemini
        $prompt = sprintf(
            'Tu es un coach en productivité. Pour l\'objectif : "%s" - "%s"

Génère 5 conseils pratiques sous forme d\'actions concrètes.

Retourne UNIQUEMENT ce JSON, sans aucun texte avant ou après :

{
    "mode_organisation": "flexible",
    "capacite_quotidienne": 4,
    "conseils": [
        "Action concrète 1",
        "Action concrète 2",
        "Action concrète 3",
        "Action concrète 4",
        "Action concrète 5"
    ]
}

Règles :
- mode_organisation : flexible, equilibre ou intensif
- capacite_quotidienne : entre 2 et 8 heures
- conseils : 5 actions spécifiques pour atteindre cet objectif',
            $titre,
            $description
        );
        
        try {
            // L'URL avec la clé (cachée dans le serveur, pas dans le navigateur)
          $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->apiKey;
            
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode !== 200) {
                // En cas d'erreur, retourne un tableau vide pour que le fallback ne s'affiche pas
                return [
                    'mode_organisation' => 'equilibre',
                    'capacite_quotidienne' => 4,
                    'conseils' => ['❌ API Gemini indisponible (Code: ' . $statusCode . ')']
                ];
            }
            
            $data = $response->toArray();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            $text = trim($text);
            $text = preg_replace('/^```json\s*/', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            
            preg_match('/\{.*\}/s', $text, $matches);
            
            if (isset($matches[0])) {
                $result = json_decode($matches[0], true);
                if (is_array($result) && isset($result['conseils'])) {
                    return $result;
                }
            }
            
            return [
                'mode_organisation' => 'equilibre',
                'capacite_quotidienne' => 4,
                'conseils' => ['❌ Impossible de parser la réponse Gemini']
            ];
            
        } catch (\Exception $e) {
            return [
                'mode_organisation' => 'equilibre',
                'capacite_quotidienne' => 4,
                'conseils' => ['❌ Erreur: ' . $e->getMessage()]
            ];
        }
    }
}