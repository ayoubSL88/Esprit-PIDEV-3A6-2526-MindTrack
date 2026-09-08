<?php
namespace App\Controller\Front\GestionExercices;

use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/app/historique')]
#[IsGranted('ROLE_USER')]
final class HistoriqueController extends AbstractController
{
    #[Route('/', name: 'front_historique_index')]
    public function index(SessionRepository $sessionRepository): Response
    {
        $user = $this->getUser();
        $sessions = $sessionRepository->findBy(['user' => $user, 'terminee' => true], ['dateFin' => 'DESC']);
        $progressions = [];
        foreach ($sessions as $session) {
            $progressions[$session->getIdSession()] = $this->calculateProgress($session);
        }
        
        // Statistiques globales
        $stats = [
            'total' => count($sessions),
            'temps_total' => array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions)),
            'moyenne_progression' => count($sessions) > 0 
                ? array_sum($progressions) / count($sessions)
                : 0
        ];
        
        return $this->render('front/gestion_exercices/historique.html.twig', [
            'sessions' => $sessions,
            'stats' => $stats,
            'progressions' => $progressions,
        ]);
    }
    
    #[Route('/{idSession}', name: 'front_historique_show')]
    public function show(int $idSession, SessionRepository $sessionRepository): Response
    {
        $session = $sessionRepository->find($idSession);

        if (!$session || $session->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Session non trouvée');
        }
        
        return $this->render('front/gestion_exercices/historique_show.html.twig', [
            'session' => $session,
            'session_progress' => $this->calculateProgress($session),
        ]);
    }

    private function calculateProgress($session): int
    {
        $exercise = $session->getExercice();
        if ($exercise === null) {
            return 0;
        }

        $totalSteps = array_values(array_filter(
            array_map('trim', preg_split('/\R+/', (string) $exercise->getDemarche())),
            static fn (string $step): bool => $step !== ''
        ));
        $completedSteps = array_values(array_unique(array_filter(
            array_map('trim', $session->getSteps() ?? []),
            static fn ($step): bool => is_string($step) && $step !== ''
        )));

        if ($totalSteps === []) {
            return 0;
        }

        $completedCount = count(array_intersect($completedSteps, $totalSteps));

        return min(100, (int) round(($completedCount / count($totalSteps)) * 100));
    }
}