<?php

namespace App\Controller\Front\GestionSuiviHabitudes;

use App\Entity\Habitude;
use App\Entity\Humeur;
use App\Entity\Rappel_habitude;
use App\Entity\Suivihabitude;
use App\Entity\Utilisateur;
use App\Form\Admin\GestionSuiviHabitudes\HabitudeType;
use App\Form\Admin\GestionSuiviHabitudes\RappelHabitudeType;
use App\Form\Admin\GestionSuiviHabitudes\SuivihabitudeType;
use App\Repository\HabitudeRepository;
use App\Repository\RappelHabitudeRepository;
use App\Repository\SuivihabitudeRepository;
use App\Service\Chat\OllamaChatService;
use App\Service\Habitude\BadgeSystemService;
use App\Service\Habitude\BadContentDetectionService;
use App\Service\Habitude\HabitChatbotService;
use App\Service\Habitude\HabitProgressService;
use App\Service\Habitude\HabitRecommendationService;
use App\Service\Habitude\HabitRiskAnalyzerService;
use App\Service\Habitude\HabitStreakService;
use App\Service\Habitude\MoodHabitCorrelationService;
use App\Service\Habitude\OpenMeteoWeatherService;
use App\Service\Habitude\SmartReminderService;
use App\Service\Security\CurrentUtilisateurResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/habitudes', name: 'front_gestion_suivi_habitudes_')]
final class OverviewController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        HabitudeRepository $habitudeRepository,
        SuivihabitudeRepository $suivihabitudeRepository,
        RappelHabitudeRepository $rappelHabitudeRepository,
        EntityManagerInterface $entityManager,
        HabitStreakService $streakService,
        HabitProgressService $progressService,
        HabitRiskAnalyzerService $riskAnalyzerService,
        HabitRecommendationService $recommendationService,
        SmartReminderService $smartReminderService,
        MoodHabitCorrelationService $moodCorrelationService,
        BadgeSystemService $badgeSystemService,
        OpenMeteoWeatherService $weatherService,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
    ): Response {
        $currentUser = $currentUtilisateurResolver->resolve();
        $weather = $weatherService->getCurrentWeather();

        $habitudeFilters = [
            'q' => (string) $request->query->get('q', ''),
            'frequence' => (string) $request->query->get('frequence', ''),
            'habitType' => (string) $request->query->get('type', ''),
            'sort' => (string) $request->query->get('sort', 'nom'),
            'direction' => (string) $request->query->get('direction', ''),
        ];

        if ($currentUser instanceof Utilisateur) {
            $habitudeFilters['owner'] = $currentUser;
        }

        $habitudes = $habitudeRepository->findAdminList($habitudeFilters);
        $suiviFilters = [
            'sort' => 'date',
            'direction' => 'DESC',
        ];
        $rappelFilters = [
            'sort' => 'heure',
            'direction' => 'ASC',
            'actif' => '1',
        ];

        if ($currentUser instanceof Utilisateur) {
            $suiviFilters['owner'] = $currentUser;
            $rappelFilters['owner'] = $currentUser;
        } else {
            $habitudes = [];
        }

        $suivis = $currentUser instanceof Utilisateur ? $suivihabitudeRepository->findAdminList($suiviFilters) : [];
        $rappels = $currentUser instanceof Utilisateur ? $rappelHabitudeRepository->findAdminList($rappelFilters) : [];

        $todayKey = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $todaySuivis = array_values(array_filter(
            $suivis,
            static fn (Suivihabitude $suivi): bool => $suivi->getDate()?->format('Y-m-d') === $todayKey
        ));

        $recentSuivis = array_slice($suivis, 0, 6);
        $todayRappels = array_slice($rappels, 0, 6);

        /** @var list<Humeur> $humeurs */
        $humeurs = $entityManager->getRepository(Humeur::class)->findBy([], ['date' => 'DESC', 'idH' => 'DESC']);
        ['advancedInsights' => $advancedInsights, 'riskDistribution' => $riskDistribution, 'bestStreak' => $bestStreak] = $this->buildAdvancedInsights(
            $habitudes,
            $rappels,
            $humeurs,
            $entityManager,
            $streakService,
            $progressService,
            $riskAnalyzerService,
            $recommendationService,
            $smartReminderService,
            $moodCorrelationService,
        );

        $advancedSummary = [
            'bestStreak' => $bestStreak,
            'highRiskHabits' => $riskDistribution['ELEVE'],
            'stableHabits' => $riskDistribution['FAIBLE'],
            'badgesUnlocked' => count($badgeSystemService->evaluate($habitudes)),
        ];

        $notificationRappels = array_map(
            static fn (Rappel_habitude $rappel): array => [
                'id' => $rappel->getIdRappel(),
                'habitude' => $rappel->getIdHabitude()?->getNom() ?? 'Habitude',
                'message' => $rappel->getMessage(),
                'heure' => $rappel->getHeureRappel(),
                'jours' => $rappel->getJours(),
            ],
            $rappels
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('front/gestion_suivi_habitudes/_habitudes_results.html.twig', [
                'habitudes' => $habitudes,
                'advancedInsights' => $advancedInsights,
            ]);
        }

        return $this->render('front/gestion_suivi_habitudes/index.html.twig', [
            'habitudes' => $habitudes,
            'advancedInsights' => $advancedInsights,
            'advancedSummary' => $advancedSummary,
            'badges' => $badgeSystemService->evaluate($habitudes),
            'todaySuivis' => $todaySuivis,
            'recentSuivis' => $recentSuivis,
            'todayRappels' => $todayRappels,
            'notificationRappels' => $notificationRappels,
            'filters' => $habitudeFilters,
            'stats' => [
                'habitudes' => $currentUser instanceof Utilisateur ? $habitudeRepository->countForUser($currentUser) : 0,
                'rappels_actifs' => $currentUser instanceof Utilisateur ? $rappelHabitudeRepository->countActiveForUser($currentUser) : 0,
                'suivis_completed' => $currentUser instanceof Utilisateur ? $suivihabitudeRepository->countCompletedForUser($currentUser) : 0,
                'suivis_total' => $currentUser instanceof Utilisateur ? $suivihabitudeRepository->countAllForUser($currentUser) : 0,
            ],
            'weather' => $weather,
            'currentUserResolved' => $currentUser instanceof Utilisateur,
        ]);
    }

    #[Route('/chatbot/respond', name: 'chatbot_respond', methods: ['POST'])]
    public function chatbotRespond(
        Request $request,
        HabitudeRepository $habitudeRepository,
        SuivihabitudeRepository $suivihabitudeRepository,
        RappelHabitudeRepository $rappelHabitudeRepository,
        EntityManagerInterface $entityManager,
        HabitStreakService $streakService,
        HabitProgressService $progressService,
        HabitRiskAnalyzerService $riskAnalyzerService,
        HabitRecommendationService $recommendationService,
        SmartReminderService $smartReminderService,
        MoodHabitCorrelationService $moodCorrelationService,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        HabitChatbotService $habitChatbotService,
        BadContentDetectionService $badContentDetectionService,
        OllamaChatService $ollamaChatService,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            return new JsonResponse(['reply' => 'Ecrivez votre question pour que je puisse vous aider.', 'highlights' => []], 400);
        }

        $currentUser = $currentUtilisateurResolver->resolve();
        if (!$currentUser instanceof Utilisateur) {
            return new JsonResponse(['reply' => 'Connectez-vous pour obtenir des conseils relies a vos habitudes.', 'highlights' => []], 401);
        }

        $moderation = $badContentDetectionService->analyze($message);
        if ($moderation['blocked']) {
            return new JsonResponse([
                'reply' => 'Votre message a ete bloque par le filtre de securite badcontent. Reformulez votre demande avec un texte plus respectueux et non dangereux.',
                'highlights' => array_values(array_filter([
                    $moderation['reason'] !== '' ? $moderation['reason'] : null,
                    $moderation['categories'] !== [] ? 'Categories: ' . implode(', ', $moderation['categories']) : null,
                ], static fn ($value): bool => is_string($value) && $value !== '')),
                'moderation' => $moderation,
            ], 422);
        }

        try {
            $habitudes = $habitudeRepository->findAdminList(['owner' => $currentUser]);
            $suivis = $suivihabitudeRepository->findAdminList([
                'owner' => $currentUser,
                'sort' => 'date',
                'direction' => 'DESC',
            ]);
            $rappels = $rappelHabitudeRepository->findAdminList([
                'owner' => $currentUser,
                'sort' => 'heure',
                'direction' => 'ASC',
                'actif' => '1',
            ]);

            /** @var list<Humeur> $humeurs */
            $humeurs = $entityManager->getRepository(Humeur::class)->findBy([], ['date' => 'DESC', 'idH' => 'DESC']);
            ['advancedInsights' => $advancedInsights] = $this->buildAdvancedInsights(
                $habitudes,
                $rappels,
                $humeurs,
                $entityManager,
                $streakService,
                $progressService,
                $riskAnalyzerService,
                $recommendationService,
                $smartReminderService,
                $moodCorrelationService,
            );

            $todayKey = (new \DateTimeImmutable('today'))->format('Y-m-d');
            $todaySuivis = array_values(array_filter(
                $suivis,
                static fn (Suivihabitude $suivi): bool => $suivi->getDate()?->format('Y-m-d') === $todayKey
            ));

            $fallbackReply = $habitChatbotService->buildReply($message, $habitudes, $rappels, $todaySuivis, $advancedInsights);

            try {
                $ollamaReply = $ollamaChatService->generateHabitReply(
                    $message,
                    $habitudes,
                    $rappels,
                    $todaySuivis,
                    $advancedInsights,
                    $fallbackReply['reply'],
                    $fallbackReply['highlights']
                );
            } catch (\Throwable) {
                $ollamaReply = null;
            }

            return new JsonResponse($ollamaReply ?? ($fallbackReply + ['source' => 'fallback']));
        } catch (\Throwable) {
            return new JsonResponse([
                'reply' => 'MindBot rencontre un probleme technique pour le moment. Reessayez dans un instant.',
                'highlights' => [],
                'source' => 'fallback',
            ], 503);
        }
    }

    #[Route('/habitude/new', name: 'habitude_new', methods: ['GET', 'POST'])]
    public function newHabitude(
        Request $request,
        EntityManagerInterface $entityManager,
        HabitudeRepository $habitudeRepository,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        BadContentDetectionService $badContentDetectionService,
    ): Response {
        $currentUser = $currentUtilisateurResolver->resolve();
        if (!$currentUser instanceof Utilisateur) {
            $this->addFlash('warning', 'Connecte-toi pour creer une habitude.');

            return $this->redirectToRoute('front_home');
        }

        $habitude = new Habitude();
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardHabitFormAgainstBadContent($form, $habitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $habitude->setIdHabitude($habitudeRepository->nextId());
            $habitude->setIdU($currentUser);
            $entityManager->persist($habitude);
            $entityManager->flush();
            $this->addFlash('success', 'Nouvelle habitude enregistree.');

            return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
        }

        return $this->render('front/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Creer une habitude',
            'form' => $form->createView(),
            'back_route' => 'front_gestion_suivi_habitudes_index',
            'helper_text' => 'Ajoutez une nouvelle habitude a suivre au quotidien.',
        ]);
    }

    #[Route('/habitude/{idHabitude}/edit', name: 'habitude_edit', methods: ['GET', 'POST'])]
    public function editHabitude(
        Request $request,
        Habitude $habitude,
        EntityManagerInterface $entityManager,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        BadContentDetectionService $badContentDetectionService,
    ): Response {
        $this->denyAccessUnlessHabitOwner($habitude, $currentUtilisateurResolver);
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardHabitFormAgainstBadContent($form, $habitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Habitude mise a jour.');

            return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
        }

        return $this->render('front/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => sprintf('Modifier %s', $habitude->getNom()),
            'form' => $form->createView(),
            'back_route' => 'front_gestion_suivi_habitudes_index',
            'helper_text' => 'Ajustez vos objectifs pour garder un suivi realiste.',
        ]);
    }

    #[Route('/habitude/{idHabitude}/delete', name: 'habitude_delete', methods: ['POST'])]
    public function deleteHabitude(
        Request $request,
        Habitude $habitude,
        EntityManagerInterface $entityManager,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
    ): Response {
        $this->denyAccessUnlessHabitOwner($habitude, $currentUtilisateurResolver);
        if ($this->isCsrfTokenValid('delete_habitude_' . $habitude->getIdHabitude(), (string) $request->request->get('_token'))) {
            $entityManager->remove($habitude);
            $entityManager->flush();
            $this->addFlash('success', 'Habitude supprimee.');
        }

        return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
    }

    #[Route('/suivi/new', name: 'suivi_new', methods: ['GET', 'POST'])]
    public function newSuivi(
        Request $request,
        EntityManagerInterface $entityManager,
        SuivihabitudeRepository $suivihabitudeRepository,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        BadContentDetectionService $badContentDetectionService,
    ): Response {
        $currentUser = $currentUtilisateurResolver->resolve();
        if (!$currentUser instanceof Utilisateur) {
            $this->addFlash('warning', 'Connecte-toi pour ajouter un suivi.');

            return $this->redirectToRoute('front_home');
        }

        $suivi = new Suivihabitude();
        $suivi->setDate(new \DateTime('today'));
        $form = $this->createForm(SuivihabitudeType::class, $suivi, [
            'current_user' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardSuiviFormAgainstBadContent($form, $suivi, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $suivi->setIdSuivi($suivihabitudeRepository->nextId());
            $habitude = $suivi->getIdHabitude();

            if ($habitude !== null && $habitude->getHabitType() !== 'NUMERIC') {
                $suivi->setEtat(true);
            }

            $entityManager->persist($suivi);
            $entityManager->flush();
            $this->addFlash('success', 'Suivi ajoute a votre journal.');

            return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
        }

        return $this->render('front/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Ajouter un suivi',
            'form' => $form->createView(),
            'back_route' => 'front_gestion_suivi_habitudes_index',
            'helper_text' => 'Enregistrez votre progression du jour.',
        ]);
    }

    #[Route('/suivi/{idSuivi}/edit', name: 'suivi_edit', methods: ['GET', 'POST'])]
    public function editSuivi(
        Request $request,
        Suivihabitude $suivihabitude,
        EntityManagerInterface $entityManager,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        BadContentDetectionService $badContentDetectionService,
    ): Response {
        $this->denyAccessUnlessSuiviOwner($suivihabitude, $currentUtilisateurResolver);
        $currentUser = $currentUtilisateurResolver->resolve();
        $form = $this->createForm(SuivihabitudeType::class, $suivihabitude, [
            'current_user' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardSuiviFormAgainstBadContent($form, $suivihabitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $habitude = $suivihabitude->getIdHabitude();
            if ($habitude !== null && $habitude->getHabitType() !== 'NUMERIC') {
                $suivihabitude->setEtat(true);
            }

            $entityManager->flush();

            if ($habitude !== null) {
                $entityManager->refresh($habitude);
            }

            $this->addFlash('success', 'Suivi mis a jour.');

            return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
        }

        return $this->render('front/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Modifier un suivi',
            'form' => $form->createView(),
            'back_route' => 'front_gestion_suivi_habitudes_index',
            'helper_text' => 'Corrigez ou completez votre progression.',
        ]);
    }

    #[Route('/suivi/{idSuivi}/delete', name: 'suivi_delete', methods: ['POST'])]
    public function deleteSuivi(
        Request $request,
        Suivihabitude $suivihabitude,
        EntityManagerInterface $entityManager,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
    ): Response {
        $this->denyAccessUnlessSuiviOwner($suivihabitude, $currentUtilisateurResolver);
        if ($this->isCsrfTokenValid('delete_suivi_' . $suivihabitude->getIdSuivi(), (string) $request->request->get('_token'))) {
            $entityManager->remove($suivihabitude);
            $entityManager->flush();
            $this->addFlash('success', 'Suivi supprime.');
        }

        return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
    }

    #[Route('/rappel/new', name: 'rappel_new', methods: ['GET', 'POST'])]
    public function newRappel(
        Request $request,
        EntityManagerInterface $entityManager,
        RappelHabitudeRepository $rappelHabitudeRepository,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        BadContentDetectionService $badContentDetectionService,
    ): Response {
        $currentUser = $currentUtilisateurResolver->resolve();
        if (!$currentUser instanceof Utilisateur) {
            $this->addFlash('warning', 'Connecte-toi pour programmer un rappel.');

            return $this->redirectToRoute('front_home');
        }

        $rappel = new Rappel_habitude();
        $rappel->setCreatedAt(new \DateTime());
        $form = $this->createForm(RappelHabitudeType::class, $rappel, [
            'current_user' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardRappelFormAgainstBadContent($form, $rappel, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $rappel->setIdRappel($rappelHabitudeRepository->nextId());
            $entityManager->persist($rappel);
            $entityManager->flush();
            $this->addFlash('success', 'Rappel programme.');

            return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
        }

        return $this->render('front/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Programmer un rappel',
            'form' => $form->createView(),
            'back_route' => 'front_gestion_suivi_habitudes_index',
            'helper_text' => 'Choisissez les jours et l heure pour recevoir votre rappel.',
        ]);
    }

    #[Route('/rappel/{idRappel}/edit', name: 'rappel_edit', methods: ['GET', 'POST'])]
    public function editRappel(
        Request $request,
        Rappel_habitude $rappelHabitude,
        EntityManagerInterface $entityManager,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
        BadContentDetectionService $badContentDetectionService,
    ): Response {
        $this->denyAccessUnlessRappelOwner($rappelHabitude, $currentUtilisateurResolver);
        $currentUser = $currentUtilisateurResolver->resolve();
        $form = $this->createForm(RappelHabitudeType::class, $rappelHabitude, [
            'current_user' => $currentUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardRappelFormAgainstBadContent($form, $rappelHabitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Rappel mis a jour.');

            return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
        }

        return $this->render('front/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Modifier un rappel',
            'form' => $form->createView(),
            'back_route' => 'front_gestion_suivi_habitudes_index',
            'helper_text' => 'Ajustez vos rappels pour garder le bon rythme.',
        ]);
    }

    #[Route('/rappel/{idRappel}/delete', name: 'rappel_delete', methods: ['POST'])]
    public function deleteRappel(
        Request $request,
        Rappel_habitude $rappelHabitude,
        EntityManagerInterface $entityManager,
        CurrentUtilisateurResolver $currentUtilisateurResolver,
    ): Response {
        $this->denyAccessUnlessRappelOwner($rappelHabitude, $currentUtilisateurResolver);
        if ($this->isCsrfTokenValid('delete_rappel_' . $rappelHabitude->getIdRappel(), (string) $request->request->get('_token'))) {
            $entityManager->remove($rappelHabitude);
            $entityManager->flush();
            $this->addFlash('success', 'Rappel supprime.');
        }

        return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
    }

    /**
     * @param list<Rappel_habitude> $rappels
     */
    private function findReminderForHabit(int $habitId, array $rappels): ?Rappel_habitude
    {
        foreach ($rappels as $rappel) {
            if ($rappel->getIdHabitude()?->getIdHabitude() === $habitId) {
                return $rappel;
            }
        }

        return null;
    }

    private function denyAccessUnlessHabitOwner(Habitude $habitude, CurrentUtilisateurResolver $currentUtilisateurResolver): void
    {
        $currentUser = $currentUtilisateurResolver->resolve();

        if (!$currentUser instanceof Utilisateur || $habitude->getIdU()?->getIdU() !== $currentUser->getIdU()) {
            throw new NotFoundHttpException('Cette habitude est introuvable.');
        }
    }

    private function denyAccessUnlessSuiviOwner(Suivihabitude $suivi, CurrentUtilisateurResolver $currentUtilisateurResolver): void
    {
        $habitude = $suivi->getIdHabitude();

        if (!$habitude instanceof Habitude) {
            throw new NotFoundHttpException('Suivi introuvable.');
        }

        $this->denyAccessUnlessHabitOwner($habitude, $currentUtilisateurResolver);
    }

    private function denyAccessUnlessRappelOwner(Rappel_habitude $rappel, CurrentUtilisateurResolver $currentUtilisateurResolver): void
    {
        $habitude = $rappel->getIdHabitude();

        if (!$habitude instanceof Habitude) {
            throw new NotFoundHttpException('Rappel introuvable.');
        }

        $this->denyAccessUnlessHabitOwner($habitude, $currentUtilisateurResolver);
    }

    /**
     * @param list<Habitude> $habitudes
     * @param list<Rappel_habitude> $rappels
     * @param list<Humeur> $humeurs
     * @return array{advancedInsights: array<int, array<string, mixed>>, riskDistribution: array<string, int>, bestStreak: int}
     */
    private function buildAdvancedInsights(
        array $habitudes,
        array $rappels,
        array $humeurs,
        EntityManagerInterface $entityManager,
        HabitStreakService $streakService,
        HabitProgressService $progressService,
        HabitRiskAnalyzerService $riskAnalyzerService,
        HabitRecommendationService $recommendationService,
        SmartReminderService $smartReminderService,
        MoodHabitCorrelationService $moodCorrelationService,
    ): array {
        $advancedInsights = [];
        $riskDistribution = [
            'ELEVE' => 0,
            'MOYEN' => 0,
            'FAIBLE' => 0,
        ];
        $bestStreak = 0;

        foreach ($habitudes as $habitude) {
            $entityManager->refresh($habitude);

            $streak = $streakService->analyze($habitude);
            $progress = $progressService->analyze($habitude);
            $risk = $riskAnalyzerService->analyze($habitude);
            $recommendation = $recommendationService->generate($habitude);
            $moodCorrelation = $moodCorrelationService->analyze($habitude, $humeurs);
            $smartReminder = $smartReminderService->suggest($habitude, $this->findReminderForHabit($habitude->getIdHabitude(), $rappels));

            $advancedInsights[$habitude->getIdHabitude()] = [
                'streak' => $streak,
                'progress' => $progress,
                'risk' => $risk,
                'recommendation' => $recommendation,
                'moodCorrelation' => $moodCorrelation,
                'smartReminder' => $smartReminder,
            ];

            $riskDistribution[$risk['riskLevel']] = ($riskDistribution[$risk['riskLevel']] ?? 0) + 1;
            $bestStreak = max($bestStreak, (int) $streak['bestStreak']);
        }

        return [
            'advancedInsights' => $advancedInsights,
            'riskDistribution' => $riskDistribution,
            'bestStreak' => $bestStreak,
        ];
    }

    private function guardHabitFormAgainstBadContent(FormInterface $form, Habitude $habitude, BadContentDetectionService $badContentDetectionService): void
    {
        $result = $badContentDetectionService->analyzeFields([
            'nom' => $habitude->getNom(),
            'objectif' => $habitude->getObjectif(),
            'unite' => $habitude->getUnit(),
        ]);

        if (!$result['blocked']) {
            return;
        }

        $message = $this->buildBadContentErrorMessage($result['label'], $result['reason'], $result['categories']);
        $fieldMap = ['nom' => 'nom', 'objectif' => 'objectif', 'unite' => 'unit'];
        $fieldName = $fieldMap[$result['field']] ?? null;

        if (is_string($fieldName) && $form->has($fieldName)) {
            $form->get($fieldName)->addError(new FormError($message));
        }
    }

    private function guardSuiviFormAgainstBadContent(FormInterface $form, Suivihabitude $suivi, BadContentDetectionService $badContentDetectionService): void
    {
        $habit = $suivi->getIdHabitude();
        if (!$habit instanceof Habitude) {
            return;
        }

        $result = $badContentDetectionService->analyzeFields([
            'nom de l habitude' => $habit->getNom(),
            'objectif de l habitude' => $habit->getObjectif(),
        ]);

        if (!$result['blocked']) {
            return;
        }

        $message = $this->buildBadContentErrorMessage('habitude selectionnee', $result['reason'], $result['categories']);
        if ($form->has('idHabitude')) {
            $form->get('idHabitude')->addError(new FormError($message));
        }
    }

    private function guardRappelFormAgainstBadContent(FormInterface $form, Rappel_habitude $rappel, BadContentDetectionService $badContentDetectionService): void
    {
        $habit = $rappel->getIdHabitude();
        $result = $badContentDetectionService->analyzeFields([
            'message' => $rappel->getMessage(),
            'jours' => $rappel->getJours(),
            'nom de l habitude' => $habit?->getNom(),
            'objectif de l habitude' => $habit?->getObjectif(),
        ]);

        if (!$result['blocked']) {
            return;
        }

        $message = $this->buildBadContentErrorMessage($result['label'], $result['reason'], $result['categories']);
        $fieldMap = ['message' => 'message', 'jours' => 'jours'];
        $fieldName = $fieldMap[$result['field']] ?? 'idHabitude';

        if ($form->has($fieldName)) {
            $form->get($fieldName)->addError(new FormError($message));
        }
    }

    /**
     * @param list<string> $categories
     */
    private function buildBadContentErrorMessage(string $fieldLabel, string $reason, array $categories): string
    {
        return 'Mauvais contenu.';
    }
}
