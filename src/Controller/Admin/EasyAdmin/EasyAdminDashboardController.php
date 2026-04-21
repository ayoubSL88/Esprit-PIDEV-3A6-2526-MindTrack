<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Exercice;
use App\Entity\Habitude;
use App\Entity\Humeur;
use App\Entity\Objectif;
use App\Entity\Session;
use App\Entity\Suivihabitude;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin-panel', routeName: 'admin_panel')]
final class EasyAdminDashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function index(): Response
    {
        $today = new \DateTimeImmutable('today');
        $sevenDaysAgo = $today->modify('-6 days')->setTime(0, 0);
        $nextWeek = $today->modify('+7 days')->setTime(23, 59, 59);

        $userRepository = $this->entityManager->getRepository(Utilisateur::class);
        $sessionRepository = $this->entityManager->getRepository(Session::class);
        $habitRepository = $this->entityManager->getRepository(Habitude::class);
        $habitTrackingRepository = $this->entityManager->getRepository(Suivihabitude::class);
        $moodRepository = $this->entityManager->getRepository(Humeur::class);
        $goalRepository = $this->entityManager->getRepository(Objectif::class);

        $usersCount = $userRepository->count([]);
        $adminUsersCount = $userRepository->count(['roleU' => 'ADMIN']);
        $standardUsersCount = $userRepository->count(['roleU' => 'USER']);
        $totpEnabledCount = $userRepository->count(['totp_enabled' => true]);
        $faceEnabledCount = $userRepository->count(['face_enabled' => true]);
        $protectedUsersCount = $this->countProtectedUsers();
        $usersNeedingSecurityReview = max(0, $usersCount - $protectedUsersCount);

        $sessionsCount = $sessionRepository->count([]);
        $completedSessionsCount = $sessionRepository->count(['terminee' => true]);
        $recentSessions = $sessionRepository->findBy([], ['dateDebut' => 'DESC'], 6);
        $recentUsers = $userRepository->findBy([], ['idU' => 'DESC'], 6);

        $exerciseCount = $this->entityManager->getRepository(Exercice::class)->count([]);
        $habitCount = $habitRepository->count([]);
        $habitTrackingCount = $habitTrackingRepository->count([]);
        $completedHabitTrackingCount = $habitTrackingRepository->count(['etat' => true]);
        $moodEntriesCount = $moodRepository->count([]);
        $goalCount = $goalRepository->count([]);

        $completionRate = $sessionsCount > 0
            ? round(($completedSessionsCount / $sessionsCount) * 100, 1)
            : 0.0;

        $habitCompletionRate = $habitTrackingCount > 0
            ? round(($completedHabitTrackingCount / $habitTrackingCount) * 100, 1)
            : 0.0;

        $avgProgress = $this->calculateAverageSessionProgress();
        $sessionsStartedThisWeek = $this->countSessionsStartedSince($sevenDaysAgo);
        $moodEntriesThisWeek = $this->countMoodEntriesSince($sevenDaysAgo);
        $goalsDueSoon = $this->countGoalsDueSoon($today, $nextWeek);
        $unusedExercisesCount = $this->countUnusedExercises();
        $topExercises = $this->findTopExercises();

        return $this->render('admin/dashboard/index.html.twig', [
            'exercices_count' => $exerciseCount,
            'users_count' => $usersCount,
            'admin_users_count' => $adminUsersCount,
            'standard_users_count' => $standardUsersCount,
            'sessions_count' => $sessionsCount,
            'completed_sessions_count' => $completedSessionsCount,
            'completion_rate' => $completionRate,
            'avg_progress' => $avgProgress,
            'habit_count' => $habitCount,
            'habit_tracking_count' => $habitTrackingCount,
            'completed_habit_tracking_count' => $completedHabitTrackingCount,
            'habit_completion_rate' => $habitCompletionRate,
            'mood_entries_count' => $moodEntriesCount,
            'mood_entries_this_week' => $moodEntriesThisWeek,
            'goal_count' => $goalCount,
            'goals_due_soon' => $goalsDueSoon,
            'sessions_started_this_week' => $sessionsStartedThisWeek,
            'face_enabled_count' => $faceEnabledCount,
            'totp_enabled_count' => $totpEnabledCount,
            'protected_users_count' => $protectedUsersCount,
            'users_needing_security_review' => $usersNeedingSecurityReview,
            'unused_exercises_count' => $unusedExercisesCount,
            'top_exercises' => $topExercises,
            'recent_users' => $recentUsers,
            'recent_sessions' => $recentSessions,
            'quick_links' => [
                [
                    'label' => 'Manage users',
                    'copy' => 'Open the EasyAdmin user CRUD with filters, detail view, and edit actions.',
                    'url' => $this->adminUrlGenerator
                        ->setController(UtilisateurCrudController::class)
                        ->setAction('index')
                        ->generateUrl(),
                ],
                [
                    'label' => 'Review exercises',
                    'copy' => 'Maintain the exercise catalog and inspect current admin content.',
                    'url' => $this->generateUrl('admin_gestion_exercices_index'),
                ],
                [
                    'label' => 'Check habits',
                    'copy' => 'Inspect adherence, reminders, and tracking quality issues.',
                    'url' => $this->generateUrl('admin_gestion_suivi_habitudes_index'),
                ],
                [
                    'label' => 'Open mood journal',
                    'copy' => 'Audit mood activity and look for missing engagement signals.',
                    'url' => $this->generateUrl('admin_gestion_humeur_index'),
                ],
            ],
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('MindTrack Admin')
            ->renderContentMaximized()
            ->disableDarkMode();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('build/app.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Overview', 'fa fa-chart-line');
        yield MenuItem::section('Users');
        yield MenuItem::linkTo(UtilisateurCrudController::class, 'Users', 'fa fa-users');
        yield MenuItem::linkToRoute('Legacy user management', 'fa fa-user-cog', 'admin_gestion_user_index');

        yield MenuItem::section('Wellbeing');
        yield MenuItem::linkToRoute('Exercises', 'fa fa-dumbbell', 'admin_gestion_exercices_index');
        yield MenuItem::linkToRoute('Habits', 'fa fa-repeat', 'admin_gestion_suivi_habitudes_index');
        yield MenuItem::linkToRoute('Mood', 'fa fa-face-smile', 'admin_gestion_humeur_index');
        yield MenuItem::linkToRoute('Goals', 'fa fa-bullseye', 'admin_gestion_objectifs_personnelles_index');
    }

    private function calculateAverageSessionProgress(): float
    {
        $recentSessionsPool = $this->entityManager->getRepository(Session::class)->findBy([], ['dateDebut' => 'DESC'], 200);
        if ($recentSessionsPool === []) {
            return 0.0;
        }

        $totalProgress = array_sum(array_map(static fn(Session $session): int => (int) ($session->getProgress() ?? 0), $recentSessionsPool));

        return round($totalProgress / count($recentSessionsPool), 1);
    }

    private function countSessionsStartedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(s.idSession)')
            ->from(Session::class, 's')
            ->andWhere('s.dateDebut >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countMoodEntriesSince(\DateTimeImmutable $since): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(h.idH)')
            ->from(Humeur::class, 'h')
            ->andWhere('h.date >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countGoalsDueSoon(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(o.idObj)')
            ->from(Objectif::class, 'o')
            ->andWhere('o.dateFin BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUnusedExercises(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.idEx)')
            ->from(Exercice::class, 'e')
            ->leftJoin(Session::class, 's', 'WITH', 's.exercice = e')
            ->andWhere('s.idSession IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{name: string, sessions: int}>
     */
    private function findTopExercises(): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('e.nom AS name, COUNT(s.idSession) AS sessions')
            ->from(Session::class, 's')
            ->innerJoin('s.exercice', 'e')
            ->groupBy('e.idEx, e.nom')
            ->orderBy('sessions', 'DESC')
            ->addOrderBy('e.nom', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn(array $row): array => [
            'name' => (string) $row['name'],
            'sessions' => (int) $row['sessions'],
        ], $rows);
    }

    private function countProtectedUsers(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.idU)')
            ->from(Utilisateur::class, 'u')
            ->andWhere('u.totp_enabled = true OR u.face_enabled = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
