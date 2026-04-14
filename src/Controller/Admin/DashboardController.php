<?php
namespace App\Controller\Admin;

use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        ExerciceRepository $exerciceRepo,
        SessionRepository $sessionRepo,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer le repository Utilisateur via l'EntityManager
        $userRepo = $entityManager->getRepository(Utilisateur::class);

        // Compter les exercices
        $exercicesCount = $exerciceRepo->count([]);
        
        // Compter les sessions
        $sessionsCount = $sessionRepo->count([]);
        
        // Compter les utilisateurs
        $usersCount = $userRepo->count([]);
        
        // Calculer la progression moyenne
        $allSessions = $sessionRepo->findAll();
        $avgProgress = 0;
        if (count($allSessions) > 0) {
            $totalProgress = array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $allSessions));
            $avgProgress = round($totalProgress / count($allSessions));
        }
        
        // Dernières sessions
        $recentSessions = $sessionRepo->findBy([], ['dateDebut' => 'DESC'], 5);
        
        return $this->render('admin/dashboard/index.html.twig', [
            'exercices_count' => $exercicesCount,
            'sessions_count' => $sessionsCount,
            'users_count' => $usersCount,
            'avg_progress' => $avgProgress,
            'recent_sessions' => $recentSessions,
        ]);
    }
}
