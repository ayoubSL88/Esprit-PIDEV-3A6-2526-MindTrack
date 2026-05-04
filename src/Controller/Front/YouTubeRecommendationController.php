<?php

namespace App\Controller\Front;

use App\Service\YouTubeRecommendationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/app/youtube')]
#[IsGranted('ROLE_USER')]
final class YouTubeRecommendationController extends AbstractController
{
    #[Route('/recommendations', name: 'front_youtube_recommendations')]
    public function recommendations(
        Request $request,
        YouTubeRecommendationService $youtubeService
    ): Response {
        $search = $request->query->get('search', '');
        $recommendations = [];
        
        if ($search) {
            // Recommandations basées sur le texte de recherche
            $recommendations = $youtubeService->recommendByText($search);
        } else {
            // Recommandations aléatoires par défaut
            $recommendations = $youtubeService->getRandomRecommendations();
        }
        
        return $this->render('front/youtube/recommendations.html.twig', [
            'recommendations' => $recommendations,
            'search' => $search
        ]);
    }
    
    #[Route('/similar/{videoId}', name: 'front_youtube_similar')]
    public function similar(
        string $videoId,
        YouTubeRecommendationService $youtubeService
    ): Response {
        $recommendations = $youtubeService->recommendByVideoId($videoId);
        
        return $this->render('front/youtube/similar.html.twig', [
            'recommendations' => $recommendations,
            'video_id' => $videoId
        ]);
    }
}