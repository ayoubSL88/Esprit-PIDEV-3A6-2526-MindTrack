<?php
namespace App\Controller\Front\GestionExercices;

use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/historique')]
final class HistoriqueController extends AbstractController
{
    #[Route('/', name: 'front_historique_index')]
    public function index(SessionRepository $sessionRepository): Response
    {
        $sessions = $sessionRepository->findBy(['terminee' => true], ['dateFin' => 'DESC']);
        
        // Statistiques globales
        $stats = [
            'total' => count($sessions),
            'temps_total' => array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions)),
            'moyenne_progression' => count($sessions) > 0 
                ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / count($sessions)
                : 0
        ];
        
        return $this->render('front/gestion_exercices/historique.html.twig', [
            'sessions' => $sessions,
            'stats' => $stats
        ]);
    }
    
    #[Route('/{idSession}', name: 'front_historique_show')]
    public function show(int $idSession, SessionRepository $sessionRepository): Response
    {
        $session = $sessionRepository->find($idSession);

        if (!$session) {
            throw $this->createNotFoundException('Session non trouvée');
        }
        
        return $this->render('front/gestion_exercices/historique_show.html.twig', [
            'session' => $session
        ]);
    }
}