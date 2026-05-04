<?php

namespace App\Service;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Psr\Log\LoggerInterface;

class YouTubeRecommendationService
{
    private string $pythonScriptPath;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(string $apiKey, LoggerInterface $logger, string $projectDir)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
        $this->pythonScriptPath = $projectDir . '/python/youtube_recommender.py';
    }

    /**
     * Exécute un script Python et retourne le résultat
     */
    private function executePython(string $action, array $params = []): array
    {
        $command = ['python', $this->pythonScriptPath, $this->apiKey, $action, ...$params];
        
        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->logger->error('Erreur Python: ' . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return json_decode($output, true) ?? [];
    }

    /**
     * Récupère des vidéos depuis YouTube et construit la matrice
     */
    public function fetchVideos(string $query = "développement personnel", int $maxResults = 50): array
    {
        return $this->executePython('fetch', [$query, (string)$maxResults]);
    }

    /**
     * Recommande des vidéos à partir d'un texte
     */
    public function recommendByText(string $text, int $topN = 6): array
    {
        $result = $this->executePython('recommend_by_text', [$text]);
        
        if (isset($result['status']) && $result['status'] === 'error') {
            return [];
        }
        
        return array_slice($result, 0, $topN);
    }

    /**
     * Recommande des vidéos similaires à une vidéo donnée
     */
    public function recommendByVideoId(string $videoId, int $topN = 6): array
    {
        $result = $this->executePython('recommend_by_id', [$videoId]);
        
        if (isset($result['status']) && $result['status'] === 'error') {
            return [];
        }
        
        return array_slice($result, 0, $topN);
    }

    /**
     * Retourne des recommandations aléatoires
     */
    public function getRandomRecommendations(int $topN = 6): array
    {
        $result = $this->executePython('random', []);
        
        if (isset($result['status']) && $result['status'] === 'error') {
            return [];
        }
        
        return array_slice($result, 0, $topN);
    }
}