<?php
namespace App\Controller\Admin\GestionExercices;

use App\Repository\SessionRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ExerciceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/progression')]
#[IsGranted('ROLE_ADMIN')]
final class AdminProgressionController extends AbstractController
{
    #[Route('/', name: 'admin_progression_index')]
    public function index(
        Request $request,
        SessionRepository $sessionRepository,
        UtilisateurRepository $userRepository,
        ExerciceRepository $exerciceRepository
    ): Response {
        // Récupérer les paramètres de filtrage
        $userId = $request->query->get('user_id', '');
        $exerciceId = $request->query->get('exercice_id', '');
        $periode = $request->query->get('periode', 'all'); // all, week, month, year
        
        // Récupérer l'utilisateur sélectionné
        $selectedUser = null;
        if ($userId) {
            $selectedUser = $userRepository->find($userId);
        }
        
        // Récupérer l'exercice sélectionné
        $selectedExercice = null;
        if ($exerciceId) {
            $selectedExercice = $exerciceRepository->find($exerciceId);
        }
        
        // Construire la requête des sessions
        $qb = $sessionRepository->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e')
            ->where('s.terminee = true');
        
        // Filtrer par utilisateur
        if ($userId) {
            $qb->andWhere('u.idU = :userId')
               ->setParameter('userId', $userId);
        }
        
        // Filtrer par exercice
        if ($exerciceId) {
            $qb->andWhere('e.idEx = :exerciceId')
               ->setParameter('exerciceId', $exerciceId);
        }
        
        // Filtrer par période
        $now = new \DateTime();
        if ($periode === 'week') {
            $start = (clone $now)->modify('-7 days');
            $qb->andWhere('s.dateFin >= :start')
               ->setParameter('start', $start);
        } elseif ($periode === 'month') {
            $start = (clone $now)->modify('-30 days');
            $qb->andWhere('s.dateFin >= :start')
               ->setParameter('start', $start);
        } elseif ($periode === 'year') {
            $start = (clone $now)->modify('-365 days');
            $qb->andWhere('s.dateFin >= :start')
               ->setParameter('start', $start);
        }
        
        $sessions = $qb->getQuery()->getResult();
        
        // Statistiques globales
        $totalSessions = count($sessions);
        $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
        $moyenneProgress = $totalSessions > 0 
            ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
            : 0;
        
        // Progression par exercice (si pas d'exercice spécifique sélectionné)
        $sessionsByExercice = [];
        if (!$exerciceId) {
            foreach ($sessions as $session) {
                $exercice = $session->getExercice();
                if ($exercice) {
                    $exerciceIdKey = $exercice->getIdEx();
                    if (!isset($sessionsByExercice[$exerciceIdKey])) {
                        $sessionsByExercice[$exerciceIdKey] = [
                            'exercice' => $exercice,
                            'total_sessions' => 0,
                            'moyenne_progression' => 0,
                            'progress_sum' => 0
                        ];
                    }
                    $sessionsByExercice[$exerciceIdKey]['total_sessions']++;
                    $sessionsByExercice[$exerciceIdKey]['progress_sum'] += $session->getProgress() ?? 0;
                }
            }
            
            foreach ($sessionsByExercice as &$item) {
                $item['moyenne_progression'] = round($item['progress_sum'] / $item['total_sessions'], 1);
            }
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
        
        // Progression chronologique par utilisateur (si un utilisateur est sélectionné)
        $chronologicalProgress = ['labels' => [], 'data' => []];
        if ($userId && $selectedUser) {
            $userSessions = array_filter($sessions, fn($s) => $s->getUser() && $s->getUser()->getIdU() == $userId);
            $userSessions = array_values($userSessions);
            
            $chronologicalProgress = [
                'labels' => [],
                'data' => []
            ];
            
            foreach ($userSessions as $index => $session) {
                $chronologicalProgress['labels'][] = 'Session #' . ($index + 1);
                $chronologicalProgress['data'][] = $session->getProgress() ?? 0;
            }
        }
        
        // Meilleurs exercices pour cet utilisateur (si sélectionné)
        $topExercicesForUser = [];
        if ($userId && $selectedUser) {
            $exerciceStats = [];
            foreach ($sessions as $session) {
                $exercice = $session->getExercice();
                if ($exercice) {
                    $id = $exercice->getIdEx();
                    if (!isset($exerciceStats[$id])) {
                        $exerciceStats[$id] = [
                            'exercice' => $exercice,
                            'count' => 0,
                            'progress_sum' => 0
                        ];
                    }
                    $exerciceStats[$id]['count']++;
                    $exerciceStats[$id]['progress_sum'] += $session->getProgress() ?? 0;
                }
            }
            
            foreach ($exerciceStats as &$item) {
                $item['avg_progress'] = round($item['progress_sum'] / $item['count'], 1);
            }
            
            uasort($exerciceStats, fn($a, $b) => $b['avg_progress'] <=> $a['avg_progress']);
            $topExercicesForUser = array_slice($exerciceStats, 0, 5);
        }
        
        // Récupérer tous les utilisateurs et exercices pour les filtres
        $allUsers = $userRepository->findAll();
        $allExercices = $exerciceRepository->findAll();
        
        return $this->render('admin/gestion_exercices/progression_index.html.twig', [
            'global_progression' => [
                'sessionsTerminees' => $totalSessions,
                'tempsTotal' => round($totalTemps / 60),
                'moyenne' => round($moyenneProgress, 1)
            ],
            'sessions_by_exercice' => array_values($sessionsByExercice),
            'evolution' => ['labels' => $labels, 'data' => $data],
            'chronological_progress' => $chronologicalProgress,
            'top_exercices_for_user' => $topExercicesForUser,
            'all_users' => $allUsers,
            'all_exercices' => $allExercices,
            'selected_user_id' => $userId,
            'selected_user' => $selectedUser,
            'selected_exercice_id' => $exerciceId,
            'selected_exercice' => $selectedExercice,
            'periode' => $periode,
        ]);
    }
}