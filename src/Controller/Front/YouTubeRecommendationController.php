<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/app/youtube')]
#[IsGranted('ROLE_USER')]
final class YouTubeRecommendationController extends AbstractController
{
    private string $apiKey = 'AIzaSyAKVvnEd0IYTM9WTvSmv9ge8cgLUk9StTM';
    
    public function __construct(private HttpClientInterface $httpClient)
    {
    }
    
    #[Route('/recommendations', name: 'front_youtube_recommendations')]
    public function recommendations(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $recommendations = [];
        $error = null;

        $suggestedKeywords = [
            'méditation' => '🧘',
            'gestion du stress' => '😌',
            'confiance en soi' => '💪',
            'motivation' => '🔥',
            'pleine conscience' => '🌿',
            'développement personnel' => '🌟',
            'estime de soi' => '💖',
            'anxiété' => '😰',
            'sommeil' => '😴'
        ];
        
        if ($search) {
            try {
                // Appel direct à l'API YouTube
                $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
                    'query' => [
                        'part' => 'snippet',
                        'q' => $search . ' développement personnel',
                        'type' => 'video',
                        'maxResults' => 12,
                        'videoDuration' => 'medium',
                        'key' => $this->apiKey
                    ]
                ]);
                
                $data = $response->toArray();
                
                // Récupérer les IDs des vidéos pour les statistiques
                $videoIds = [];
                foreach ($data['items'] as $item) {
                    $videoIds[] = $item['id']['videoId'];
                }
                
                // Récupérer les statistiques (vues, likes)
                $statsData = [];
                if (!empty($videoIds)) {
                    $statsResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/videos', [
                        'query' => [
                            'part' => 'statistics',
                            'id' => implode(',', $videoIds),
                            'key' => $this->apiKey
                        ]
                    ]);
                    $statsData = $statsResponse->toArray();
                    
                    // Créer un tableau associatif ID -> stats
                    $statsMap = [];
                    foreach ($statsData['items'] as $statItem) {
                        $statsMap[$statItem['id']] = $statItem['statistics'];
                    }
                }
                
                // Construire les recommandations
                foreach ($data['items'] as $index => $item) {
                    $videoId = $item['id']['videoId'];
                    $stats = $statsMap[$videoId] ?? [];
                    $views = isset($stats['viewCount']) ? (int)$stats['viewCount'] : 0;
                    
                    $recommendations[] = [
                        'id' => $videoId,
                        'title' => $item['snippet']['title'],
                        'description' => $item['snippet']['description'],
                        'url' => 'https://www.youtube.com/watch?v=' . $videoId,
                        'thumbnail' => $item['snippet']['thumbnails']['medium']['url'] ?? $item['snippet']['thumbnails']['default']['url'],
                        'channel' => $item['snippet']['channelTitle'],
                        'views' => $views,
                        'similarity_score' => round(max(0, 100 - ($index * 8)), 1) // Score fictif basé sur le rang
                    ];
                }
                
            } catch (\Exception $e) {
                $error = "Erreur lors de la recherche sur YouTube : " . $e->getMessage();
            }
        }
        
        return $this->render('front/gestion_exercices/youtube_recommendations.html.twig', [
            'recommendations' => $recommendations,
            'search' => $search,
            'error' => $error,
            'suggested_keywords' => $suggestedKeywords
        ]);
    }
}