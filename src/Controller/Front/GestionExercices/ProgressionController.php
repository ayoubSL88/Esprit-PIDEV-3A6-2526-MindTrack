<?php
namespace App\Controller\Front\GestionExercices;

use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/app/progression')]
//#[IsGranted('ROLE_USER')]
final class ProgressionController extends AbstractController
{
    #[Route('/', name: 'front_progression_index')]
    public function index(SessionRepository $sessionRepository): Response
    {
        //$user = $this->getUser();
        $sessions = $sessionRepository->findBy(['terminee' => true]);
        
        // Calcul des stats
        $totalSessions = count($sessions);
        $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
        $moyenneProgress = $totalSessions > 0 
            ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
            : 0;
        
        // Progression par exercice
        $sessionsByExercice = [];
        foreach ($sessions as $session) {
            $exerciceId = $session->getExercice()->getIdEx();
            if (!isset($sessionsByExercice[$exerciceId])) {
                $sessionsByExercice[$exerciceId] = [
                    'exercice' => $session->getExercice(),
                    'total_sessions' => 0,
                    'moyenne_progression' => 0,
                    'progress_sum' => 0
                ];
            }
            $sessionsByExercice[$exerciceId]['total_sessions']++;
            $sessionsByExercice[$exerciceId]['progress_sum'] += $session->getProgress() ?? 0;
        }
        
        foreach ($sessionsByExercice as &$item) {
            $item['moyenne_progression'] = round($item['progress_sum'] / $item['total_sessions'], 1);
        }
        
        // Données pour le graphique (7 derniers jours)
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $labels[] = $date->format('d/m');
            $count = 0;
            foreach ($sessions as $session) {
                if ($session->getDateFin() && $session->getDateFin()->format('Y-m-d') === $date->format('Y-m-d')) {
                    $count++;
                }
            }
            $data[] = $count;
        }
        
        return $this->render('front/gestion_exercices/progression.html.twig', [
            'global_progression' => ['sessionsTerminees' => $totalSessions, 'tempsTotal' => $totalTemps],
            'sessions_by_exercice' => array_values($sessionsByExercice),
            'evolution' => ['labels' => $labels, 'data' => $data]
        ]);
    }
}