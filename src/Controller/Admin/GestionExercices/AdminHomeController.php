<?php

namespace App\Controller\Admin\GestionExercices;

use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/gestion-exercices')]
#[IsGranted('ROLE_ADMIN')]
final class AdminHomeController extends AbstractController
{
    #[Route('/', name: 'admin_gestion_exercices_home')]
    #[Route('/dashboard', name: 'admin_gestion_exercices_dashboard')]
    public function dashboard(
        Request $request,
        ExerciceRepository $exerciceRepo,
        SessionRepository $sessionRepo,
        UtilisateurRepository $userRepo
    ): Response {
        // Récupération des filtres
        $selectedUserId = $request->query->get('user_id', '');
        $periode = $request->query->get('periode', 'all');// all, week, month, year
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');
        
        // Liste des utilisateurs pour le filtre
        $allUsers = $userRepo->findAll();
        
        // Construction de la requête avec filtres
        $sessionsQuery = $sessionRepo->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e');
        
        // Filtre par utilisateur
        if ($selectedUserId) {
            $sessionsQuery->andWhere('u.idU = :userId')
                ->setParameter('userId', $selectedUserId);
        }
        
        // Filtre par période
        $now = new \DateTime();
        if ($periode === 'week') {
            $start = (clone $now)->modify('-7 days');
            $sessionsQuery->andWhere('s.dateDebut >= :start')
                ->setParameter('start', $start);
        } elseif ($periode === 'month') {
            $start = (clone $now)->modify('-30 days');
            $sessionsQuery->andWhere('s.dateDebut >= :start')
                ->setParameter('start', $start);
        } elseif ($periode === 'year') {
            $start = (clone $now)->modify('-365 days');
            $sessionsQuery->andWhere('s.dateDebut >= :start')
                ->setParameter('start', $start);
        }
        
        // Filtre par dates personnalisées
        if ($dateDebut) {
            $sessionsQuery->andWhere('s.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }
        if ($dateFin) {
            $sessionsQuery->andWhere('s.dateDebut <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin . ' 23:59:59'));
        }
        
        $allSessions = $sessionsQuery->getQuery()->getResult();
        
        // Statistiques des exercices (non filtrées par utilisateur pour la vue d'ensemble)
        $totalExercices = $exerciceRepo->count([]);
        $exercicesParDifficulte = [
            'FACILE' => $exerciceRepo->count(['difficulte' => 'FACILE']),
            'MOYEN' => $exerciceRepo->count(['difficulte' => 'MOYEN']),
            'DIFFICILE' => $exerciceRepo->count(['difficulte' => 'DIFFICILE']),
        ];
        
        // Types d'exercices
        $allExercices = $exerciceRepo->findAll();
        $typeStats = [];
        foreach ($allExercices as $exercice) {
            $type = $exercice->getType();
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = 0;
            }
            $typeStats[$type]++;
        }
        arsort($typeStats);
        
        // Statistiques des sessions (filtrées)
        $totalSessions = count($allSessions);
        $sessionsTerminees = count(array_filter($allSessions, fn($s) => $s->getTerminee()));
        $sessionsEnCours = $totalSessions - $sessionsTerminees;
        
        // Temps total passé
        $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $allSessions));
        $totalTempsMinutes = round($totalTemps / 60);
        
        // Progression moyenne
        $avgProgress = $totalSessions > 0 
            ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $allSessions)) / $totalSessions 
            : 0;
        
        // Données pour le graphique (7 derniers jours, filtrées)
        $sessionsParJour = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $dateKey = $date->format('Y-m-d');
            $sessionsParJour[$dateKey] = [
                'label' => $date->format('d/m'),
                'count' => 0,
                'temps' => 0
            ];
        }
        
        foreach ($allSessions as $session) {
            $dateFin = $session->getDateFin();
            if ($dateFin) {
                $dateKey = $dateFin->format('Y-m-d');
                if (isset($sessionsParJour[$dateKey])) {
                    $sessionsParJour[$dateKey]['count']++;
                    $sessionsParJour[$dateKey]['temps'] += $session->getDureeReelle() ?? 0;
                }
            }
        }
        
        // ========== TOP EXERCICES LES PLUS PRATIQUES ==========
        $exercicesAvecSessions = [];
        foreach ($allSessions as $session) {
            $exercice = $session->getExercice();
            if ($exercice) {
                $id = $exercice->getIdEx();
                if (!isset($exercicesAvecSessions[$id])) {
                    $exercicesAvecSessions[$id] = [
                        'nom' => $exercice->getNom(),
                        'type' => $exercice->getType(),
                        'count' => 0,
                        'avg_progress' => 0,
                        'progress_sum' => 0,
                        'temps_total' => 0
                    ];
                }
                $exercicesAvecSessions[$id]['count']++;
                $exercicesAvecSessions[$id]['progress_sum'] += $session->getProgress() ?? 0;
                $exercicesAvecSessions[$id]['temps_total'] += $session->getDureeReelle() ?? 0;
            }
        }
        
        foreach ($exercicesAvecSessions as &$item) {
            $item['avg_progress'] = $item['count'] > 0 
                ? round($item['progress_sum'] / $item['count'], 1) 
                : 0;
            $item['temps_moyen'] = $item['count'] > 0 
                ? round(($item['temps_total'] / $item['count']) / 60, 1)
                : 0;
        }
        
        uasort($exercicesAvecSessions, fn($a, $b) => $b['count'] <=> $a['count']);
        $topExercices = array_slice($exercicesAvecSessions, 0, 5, true);
        
        // Statistiques utilisateurs
        $totalUsers = $userRepo->count([]);
        $totalAdmins = $userRepo->count(['roleU' => 'ADMIN']);
        $totalUsersOnly = $totalUsers - $totalAdmins;
        
        // Utilisateurs actifs (avec au moins une session pendant la période filtrée)
        $usersActifs = [];
        foreach ($allSessions as $session) {
            $user = $session->getUser();
            if ($user) {
                $usersActifs[$user->getIdU()] = $user;
            }
        }
        $totalUsersActifs = count($usersActifs);
        
        // Statistiques de progression par utilisateur (pour le tableau) Top Utilisateur
        $usersProgressStats = [];
        foreach ($allUsers as $user) {
            $userSessions = array_filter($allSessions, fn($s) => $s->getUser() && $s->getUser()->getIdU() === $user->getIdU());
            $userSessionsCount = count($userSessions);
            $userAvgProgress = $userSessionsCount > 0 
                ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $userSessions)) / $userSessionsCount
                : 0;
            $userTotalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $userSessions));
            
            $usersProgressStats[] = [
                'user' => $user,
                'sessions_count' => $userSessionsCount,
                'avg_progress' => round($userAvgProgress, 1),
                'total_temps' => round($userTotalTemps / 60)
            ];
        }
        
        usort($usersProgressStats, fn($a, $b) => $b['sessions_count'] <=> $a['sessions_count']);
        $topUsersProgress = array_slice($usersProgressStats, 0, 10);
        
        // Dernières sessions ou activités (filtrées)
        $recentSessions = array_slice($allSessions, 0, 10);
        $recentExercices = $exerciceRepo->findBy([], ['date_creation' => 'DESC'], 5);
        
        // Informations sur l'utilisateur sélectionné (pour affichage)
        $selectedUser = null;
        if ($selectedUserId) {
            $selectedUser = $userRepo->find($selectedUserId);
        }
        
        return $this->render('admin/gestion_exercices/home.html.twig', [
            // Stats générales
            'stats' => [
                'total_exercices' => $totalExercices,
                'exercices_par_difficulte' => $exercicesParDifficulte,
                'types_exercices' => $typeStats,
                'total_sessions' => $totalSessions,
                'sessions_terminees' => $sessionsTerminees,
                'sessions_encours' => $sessionsEnCours,
                'temps_total' => $totalTempsMinutes,
                'progression_moyenne' => round($avgProgress, 1),
                'total_users' => $totalUsers,
                'total_users_only' => $totalUsersOnly,
                'total_admins' => $totalAdmins,
                'total_users_actifs' => $totalUsersActifs,
            ],
            // Graphiques
            'chart_sessions' => [
                'labels' => array_column($sessionsParJour, 'label'),
                'data' => array_column($sessionsParJour, 'count'),
            ],
            'chart_temps' => [
                'labels' => array_column($sessionsParJour, 'label'),
                'data' => array_map(fn($item) => round($item['temps'] / 60, 1), $sessionsParJour),
            ],
            // Listes
            'recent_exercices' => $recentExercices,
            'top_exercices' => $topExercices,
            'recent_sessions' => $recentSessions,
            'top_users_progress' => $topUsersProgress,
            // Filtres
            'all_users' => $allUsers,
            'selected_user_id' => $selectedUserId,
            'selected_user' => $selectedUser,
            'periode' => $periode,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);
    }
    
    #[Route('/statistiques', name: 'admin_gestion_exercices_statistiques')]
    public function statistiques(
        Request $request,
        ExerciceRepository $exerciceRepo,
        SessionRepository $sessionRepo,
        UtilisateurRepository $userRepo
    ): Response {
        // Récupération des filtres
        $selectedUserId = $request->query->get('user_id', '');
        $selectedExerciceId = $request->query->get('exercice_id', '');
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');
        
        $allUsers = $userRepo->findAll();
        $allExercices = $exerciceRepo->findAll();
        
        // Construction de la requête avec filtres
        $sessionsQuery = $sessionRepo->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e')
            ->where('s.terminee = true');
        
        if ($selectedUserId) {
            $sessionsQuery->andWhere('u.idU = :userId')
                ->setParameter('userId', $selectedUserId);
        }
        
        if ($selectedExerciceId) {
            $sessionsQuery->andWhere('e.idEx = :exerciceId')
                ->setParameter('exerciceId', $selectedExerciceId);
        }
        
        if ($dateDebut) {
            $sessionsQuery->andWhere('s.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', new \DateTime($dateDebut));
        }
        if ($dateFin) {
            $sessionsQuery->andWhere('s.dateDebut <= :dateFin')
                ->setParameter('dateFin', new \DateTime($dateFin . ' 23:59:59'));
        }
        
        $allSessions = $sessionsQuery->getQuery()->getResult();
        
        // Évolution mensuelle (12 derniers mois)
        $evolutionMensuelle = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = new \DateTime("-$i months");
            $moisKey = $date->format('Y-m');
            $evolutionMensuelle[$moisKey] = [
                'label' => $date->format('M Y'),
                'sessions' => 0,
                'temps' => 0,
                'progress' => 0
            ];
        }
        
        foreach ($allSessions as $session) {
            $dateFin = $session->getDateFin();
            if ($dateFin) {
                $moisKey = $dateFin->format('Y-m');
                if (isset($evolutionMensuelle[$moisKey])) {
                    $evolutionMensuelle[$moisKey]['sessions']++;
                    $evolutionMensuelle[$moisKey]['temps'] += $session->getDureeReelle() ?? 0;
                    $evolutionMensuelle[$moisKey]['progress'] += $session->getProgress() ?? 0;
                }
            }
        }
        
        foreach ($evolutionMensuelle as &$item) {
            if ($item['sessions'] > 0) {
                $item['progress'] = round($item['progress'] / $item['sessions'], 1);
            }
        }
        
        // Distribution des difficultés
        $difficulteStats = [
            'FACILE' => $exerciceRepo->count(['difficulte' => 'FACILE']),
            'MOYEN' => $exerciceRepo->count(['difficulte' => 'MOYEN']),
            'DIFFICILE' => $exerciceRepo->count(['difficulte' => 'DIFFICILE']),
        ];
        
        // Distribution des types d'exercices
        $exercices = $exerciceRepo->findAll();
        $typeStats = [];
        foreach ($exercices as $exercice) {
            $type = $exercice->getType();
            if (!isset($typeStats[$type])) {
                $typeStats[$type] = 0;
            }
            $typeStats[$type]++;
        }
        arsort($typeStats);
        
        // Top utilisateurs
        $userSessions = [];
        foreach ($allSessions as $session) {
            $user = $session->getUser();
            if ($user) {
                $id = $user->getIdU();
                if (!isset($userSessions[$id])) {
                    $userSessions[$id] = [
                        'user' => $user,
                        'sessions' => 0,
                        'temps' => 0,
                        'progress_total' => 0
                    ];
                }
                $userSessions[$id]['sessions']++;
                $userSessions[$id]['temps'] += $session->getDureeReelle() ?? 0;
                $userSessions[$id]['progress_total'] += $session->getProgress() ?? 0;
            }
        }
        
        foreach ($userSessions as &$item) {
            $item['progress_moyen'] = $item['sessions'] > 0 
                ? round($item['progress_total'] / $item['sessions'], 1) 
                : 0;
            $item['temps_minutes'] = round($item['temps'] / 60);
        }
        
        uasort($userSessions, fn($a, $b) => $b['sessions'] <=> $a['sessions']);
        $topUsers = array_slice($userSessions, 0, 10, true);
        
        // Top exercices (si filtre utilisateur)
        $topExercicesForUser = [];
        if ($selectedUserId) {
            $exerciceStats = [];
            foreach ($allSessions as $session) {
                $exercice = $session->getExercice();
                if ($exercice) {
                    $id = $exercice->getIdEx();
                    if (!isset($exerciceStats[$id])) {
                        $exerciceStats[$id] = [
                            'exercice' => $exercice,
                            'count' => 0,
                            'progress_sum' => 0,
                            'temps_total' => 0
                        ];
                    }
                    $exerciceStats[$id]['count']++;
                    $exerciceStats[$id]['progress_sum'] += $session->getProgress() ?? 0;
                    $exerciceStats[$id]['temps_total'] += $session->getDureeReelle() ?? 0;
                }
            }
            
            foreach ($exerciceStats as &$item) {
                $item['avg_progress'] = round($item['progress_sum'] / $item['count'], 1);
                $item['avg_temps'] = round(($item['temps_total'] / $item['count']) / 60, 1);
            }
            
            uasort($exerciceStats, fn($a, $b) => $b['count'] <=> $a['count']);
            $topExercicesForUser = array_slice($exerciceStats, 0, 5);
        }
        
        $selectedUser = null;
        if ($selectedUserId) {
            $selectedUser = $userRepo->find($selectedUserId);
        }
        
        $selectedExercice = null;
        if ($selectedExerciceId) {
            $selectedExercice = $exerciceRepo->find($selectedExerciceId);
        }
        
        return $this->render('admin/gestion_exercices/statistiques.html.twig', [
            'evolution_mensuelle' => array_values($evolutionMensuelle),
            'difficulte_stats' => $difficulteStats,
            'type_stats' => $typeStats,
            'top_users' => $topUsers,
            'top_exercices_for_user' => $topExercicesForUser,
            'total_sessions' => count($allSessions),
            'total_temps' => round(array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $allSessions)) / 60),
            'all_users' => $allUsers,
            'all_exercices' => $allExercices,
            'selected_user_id' => $selectedUserId,
            'selected_user' => $selectedUser,
            'selected_exercice_id' => $selectedExerciceId,
            'selected_exercice' => $selectedExercice,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin instanceof \DateTime ? $dateFin->format('Y-m-d') : $dateFin,
        ]);
    }
}