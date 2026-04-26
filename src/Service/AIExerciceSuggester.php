<?php
namespace App\Service;

use OpenAI;
use App\Entity\Exercice;
use App\Entity\Utilisateur;
use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;

class AIExerciceSuggester
{
    private ?OpenAI\Client $openai = null;
    private ?object $gemini = null;
    private ExerciceRepository $exerciceRepo;
    private SessionRepository $sessionRepo;
    private string $aiProvider; // 'openai' ou 'gemini'

    public function __construct(
        ExerciceRepository $exerciceRepo,
        SessionRepository $sessionRepo,
        string $openaiApiKey = null,
        string $geminiApiKey = null
    ) {
        $this->exerciceRepo = $exerciceRepo;
        $this->sessionRepo = $sessionRepo;
        
        // Choix du provider : OpenAI si dispo, sinon Gemini, sinon fallback
        if (!empty($openaiApiKey) && $openaiApiKey !== 'your_openai_api_key_here') {
            $this->openai = OpenAI::client($openaiApiKey);
            $this->aiProvider = 'openai';
        } elseif (!empty($geminiApiKey) && $geminiApiKey !== 'your_gemini_api_key_here') {
            $this->initGemini($geminiApiKey);
            $this->aiProvider = 'gemini';
        } else {
            $this->aiProvider = 'fallback';
        }
    }

    /**
     * Initialise le client Gemini
     */
    private function initGemini(string $apiKey): void
    {
        // Utilisation de cURL direct (sans package externe)
        $this->gemini = (object) [
            'apiKey' => $apiKey,
        ];
    }

    /**
     * Suggestion basée sur l'humeur (1-10) avec IA (OpenAI ou Gemini)
     */
    public function suggestByMood(int $mood, ?Utilisateur $user = null): ?Exercice
    {
        // Si aucune IA disponible, utiliser le fallback direct
        if ($this->aiProvider === 'fallback') {
            return $this->fallbackSuggestByMood($mood);
        }
        
        try {
            $exerciceNames = $this->getAllExerciceNames();
            
            if (empty($exerciceNames)) {
                return $this->getFallbackExercice();
            }
            
            $userContext = $this->buildUserContext($user);
            $prompt = $this->buildPrompt($mood, $userContext, $exerciceNames);
            
            // Appel à l'IA selon le provider
            $suggestedName = $this->callAI($prompt);
            
            // Trouver l'exercice correspondant
            $exercice = $this->exerciceRepo->findOneBy(['nom' => $suggestedName]);
            
            if ($exercice) {
                return $exercice;
            }
            
            // Recherche par similarité
            $exercice = $this->findBySimilarName($suggestedName);
            if ($exercice) {
                return $exercice;
            }
            
        } catch (\Exception $e) {
            error_log('AI API error: ' . $e->getMessage());
        }
        
        return $this->fallbackSuggestByMood($mood);
    }

    /**
     * Appel à l'API IA (OpenAI ou Gemini)
     */
    private function callAI(string $prompt): string
    {
        if ($this->aiProvider === 'openai') {
            return $this->callOpenAI($prompt);
        } else {
            return $this->callGemini($prompt);
        }
    }

    /**
     * Appel à l'API OpenAI
     */
    private function callOpenAI(string $prompt): string
    {
        $result = $this->openai->chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un assistant expert en bien-être. Tu ne réponds que par le nom d\'un exercice.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 50,
            'temperature' => 0.7,
        ]);

        return trim($result->choices[0]->message->content);
    }

    /**
     * Appel à l'API Gemini (via cURL, sans package)
     */
    private function callGemini(string $prompt): string
    {
        $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $this->gemini->apiKey);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]));
        
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);
        
        return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
    }

    /**
     * Construit le contexte utilisateur
     */
    private function buildUserContext(?Utilisateur $user): string
    {
        if (!$user) {
            return "";
        }
        
        $context = "L'utilisateur s'appelle {$user->getPrenomU()} {$user->getNomU()}, âgé de {$user->getAgeU()} ans. ";
        $context .= "Ses exercices préférés sont: " . $this->getUserFavoriteTypes($user);
        
        return $context;
    }

    /**
     * Construit le prompt pour l'IA
     */
    private function buildPrompt(int $mood, string $userContext, array $exerciceNames): string
    {
        return "Tu es un coach en bien-être expert. 
                   $userContext
                   L'utilisateur a une humeur de {$mood}/10 (1=très mal/très anxieux, 10=très bien/énergique).
                   
                   Propose UN SEUL exercice de bien-être adapté à son état.
                   
                   Liste des exercices disponibles : " . implode(', ', $exerciceNames) . "
                   
                   Règles importantes :
                   - Humeur 1-3 → exercices apaisants, anti-anxiété, ancrage
                   - Humeur 4-6 → exercices de respiration, méditation légère
                   - Humeur 7-8 → exercices de gratitude, visualisation positive
                   - Humeur 9-10 → exercices dynamiques, énergisants
                   
                   Réponds UNIQUEMENT par le NOM EXACT de l'exercice, rien d'autre.";
    }

    /**
     * Suggestion basée sur l'historique (conservée)
     */
    public function suggestByHistory(Utilisateur $user): ?Exercice
    {
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true], 
            ['dateFin' => 'DESC']
        );
        
        if (!$lastSession || !$lastSession->getExercice()) {
            return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE']);
        }
        
        $lastExercice = $lastSession->getExercice();
        $lastDifficulte = $lastExercice->getDifficulte();
        
        switch ($lastDifficulte) {
            case 'FACILE':
                return $this->exerciceRepo->findOneBy(['difficulte' => 'MOYEN']);
            case 'MOYEN':
                return $this->exerciceRepo->findOneBy(['difficulte' => 'DIFFICILE']);
            case 'DIFFICILE':
                $otherExercice = $this->exerciceRepo->findOneBy(['difficulte' => 'DIFFICILE']);
                if ($otherExercice && $otherExercice !== $lastExercice) {
                    return $otherExercice;
                }
                return $this->exerciceRepo->findOneBy(['difficulte' => 'MOYEN']);
            default:
                return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE']);
        }
    }

    /**
     * Suggestion combinée (humeur + historique)
     */
    public function suggestCombined(Utilisateur $user, int $mood): ?Exercice
    {
        if ($mood <= 3 || $mood >= 9) {
            return $this->suggestByMood($mood, $user);
        }
        
        $historySuggestion = $this->suggestByHistory($user);
        
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true],
            ['dateFin' => 'DESC']
        );
        
        if ($lastSession && $historySuggestion === $lastSession->getExercice()) {
            return $this->suggestByMood($mood, $user);
        }
        
        return $historySuggestion;
    }

    /**
     * Suggestion aléatoire
     */
    public function getRandomSuggestion(): ?Exercice
    {
        $exercices = $this->exerciceRepo->findAll();
        return empty($exercices) ? null : $exercices[array_rand($exercices)];
    }

    /**
     * Fallback mapping (votre code original)
     */
    private function fallbackSuggestByMood(int $mood): ?Exercice
    {
        $mapping = [
            1 => '5-4-3-2-1 (retour au présent)',
            2 => 'STOP (Arrêter la panique)',
            3 => 'Ancrage par les pieds',
            4 => 'Respiration carrée (box breathing)',
            5 => 'Scan corporel complet',
            6 => 'Méditation de pleine conscience',
            7 => '3 choses pour lesquelles je suis reconnaissant',
            8 => 'Mon lieu sûr',
            9 => 'Danse de la victoire',
            10 => 'Rituel du matin',
        ];
        
        $nomExercice = $mapping[$mood] ?? $mapping[5];
        $exercice = $this->exerciceRepo->findOneBy(['nom' => $nomExercice]);
        
        return $exercice ?? $this->getFallbackExercice();
    }

    private function findBySimilarName(string $searchName): ?Exercice
    {
        $exercices = $this->exerciceRepo->findAll();
        
        foreach ($exercices as $exercice) {
            $nom = $exercice->getNom();
            if (str_contains(mb_strtolower($nom), mb_strtolower($searchName)) ||
                str_contains(mb_strtolower($searchName), mb_strtolower($nom))) {
                return $exercice;
            }
        }
        
        return null;
    }

    private function getAllExerciceNames(): array
    {
        $exercices = $this->exerciceRepo->findAll();
        return array_map(fn($e) => $e->getNom(), $exercices);
    }

    private function getUserFavoriteTypes(Utilisateur $user): string
    {
        $sessions = $this->sessionRepo->findBy(['user' => $user, 'terminee' => true]);
        
        if (empty($sessions)) {
            return "aucun exercice encore pratiqué";
        }
        
        $typeCount = [];
        foreach ($sessions as $session) {
            $exercice = $session->getExercice();
            if ($exercice) {
                $type = $exercice->getType();
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
            }
        }
        
        if (empty($typeCount)) {
            return "aucun exercice encore pratiqué";
        }
        
        arsort($typeCount);
        $favoriteTypes = array_slice(array_keys($typeCount), 0, 2);
        
        return implode(', ', $favoriteTypes);
    }

    private function getFallbackExercice(): ?Exercice
    {
        return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE'])
            ?? $this->exerciceRepo->findOneBy([]);
    }
}