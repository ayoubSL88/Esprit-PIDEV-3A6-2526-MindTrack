<?php

namespace App\Controller\Front;

use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function index(ExerciceRepository $exerciceRepo, SessionRepository $sessionRepo): Response
    {
        $user = $this->getUser();
        
        // Derniers exercices ajoutés
        $recentExercices = $exerciceRepo->findBy([], ['date_creation' => 'DESC'], 6);
        
        // Statistiques utilisateur (si connecté)
        $stats = null;
        if ($user) {
            $sessions = $sessionRepo->findBy(['user' => $user, 'terminee' => true]);
            $totalSessions = count($sessions);
            $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
            $moyenneProgress = $totalSessions > 0 
                ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
                : 0;
            
            $stats = [
                'total' => $totalSessions,
                'temps' => round($totalTemps / 60),
                'moyenne' => round($moyenneProgress, 1),
                'recent_sessions' => $sessionRepo->findBy(['user' => $user, 'terminee' => true], ['dateFin' => 'DESC'], 5)
            ];
        }
        
        return $this->render('front/home/index.html.twig', [
            'recent_exercices' => $recentExercices,
            'stats' => $stats
        ]);
    }
}
