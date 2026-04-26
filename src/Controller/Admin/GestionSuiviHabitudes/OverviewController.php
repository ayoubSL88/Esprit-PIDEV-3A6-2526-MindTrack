<?php

namespace App\Controller\Admin\GestionSuiviHabitudes;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Entity\Suivihabitude;
use App\Form\Admin\GestionSuiviHabitudes\HabitudeType;
use App\Form\Admin\GestionSuiviHabitudes\RappelHabitudeType;
use App\Form\Admin\GestionSuiviHabitudes\SuivihabitudeType;
use App\Repository\HabitudeRepository;
use App\Repository\RappelHabitudeRepository;
use App\Repository\SuivihabitudeRepository;
use App\Service\Habitude\BadContentDetectionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/habitudes', name: 'admin_gestion_suivi_habitudes_')]
final class OverviewController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        HabitudeRepository $habitudeRepository,
        SuivihabitudeRepository $suivihabitudeRepository,
        RappelHabitudeRepository $rappelHabitudeRepository,
    ): Response {
        $habitudeFilters = [
            'q' => (string) $request->query->get('hab_q', ''),
            'frequence' => (string) $request->query->get('hab_frequence', ''),
            'habitType' => (string) $request->query->get('hab_type', ''),
            'sort' => (string) $request->query->get('hab_sort', 'nom'),
            'direction' => (string) $request->query->get('hab_direction', 'ASC'),
        ];

        $suiviFilters = [
            'q' => (string) $request->query->get('suivi_q', ''),
            'habitude' => (string) $request->query->get('suivi_habitude', ''),
            'etat' => (string) $request->query->get('suivi_etat', ''),
            'sort' => (string) $request->query->get('suivi_sort', 'date'),
            'direction' => (string) $request->query->get('suivi_direction', 'DESC'),
        ];

        $rappelFilters = [
            'q' => (string) $request->query->get('rappel_q', ''),
            'habitude' => (string) $request->query->get('rappel_habitude', ''),
            'actif' => (string) $request->query->get('rappel_actif', ''),
            'sort' => (string) $request->query->get('rappel_sort', 'created'),
            'direction' => (string) $request->query->get('rappel_direction', 'DESC'),
        ];

        $habitudes = $habitudeRepository->findAdminList($habitudeFilters);

        $context = [
            'habitudes' => $habitudes,
            'suivis' => $suivihabitudeRepository->findAdminList($suiviFilters),
            'rappels' => $rappelHabitudeRepository->findAdminList($rappelFilters),
            'habitudeFilters' => $habitudeFilters,
            'suiviFilters' => $suiviFilters,
            'rappelFilters' => $rappelFilters,
            'habitudeChoices' => $habitudes !== [] ? $habitudes : $habitudeRepository->findBy([], ['nom' => 'ASC']),
            'stats' => [
                'habitudes' => $habitudeRepository->countAll(),
                'habitudes_boolean' => $habitudeRepository->countByType('BOOLEAN'),
                'habitudes_numeric' => $habitudeRepository->countByType('NUMERIC'),
                'suivis' => $suivihabitudeRepository->countAll(),
                'suivis_completed' => $suivihabitudeRepository->countCompleted(),
                'rappels' => $rappelHabitudeRepository->countAll(),
                'rappels_actifs' => $rappelHabitudeRepository->countActive(),
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/gestion_suivi_habitudes/_content.html.twig', $context);
        }

        return $this->render('admin/gestion_suivi_habitudes/index.html.twig', $context);
    }

    #[Route('/habitude/new', name: 'habitude_new', methods: ['GET', 'POST'])]
    public function newHabitude(Request $request, EntityManagerInterface $entityManager, HabitudeRepository $habitudeRepository, BadContentDetectionService $badContentDetectionService): Response
    {
        $habitude = new Habitude();
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardHabitFormAgainstBadContent($form, $habitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $habitude->setIdHabitude($habitudeRepository->nextId());
            $entityManager->persist($habitude);
            $entityManager->flush();
            $this->addFlash('success', 'Habitude ajoutee avec succes.');

            return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
        }

        return $this->render('admin/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Nouvelle habitude',
            'form' => $form->createView(),
            'entity_label' => 'habitude',
            'back_route' => 'admin_gestion_suivi_habitudes_index',
        ]);
    }

    #[Route('/habitude/{idHabitude}/edit', name: 'habitude_edit', methods: ['GET', 'POST'])]
    public function editHabitude(Request $request, Habitude $habitude, EntityManagerInterface $entityManager, BadContentDetectionService $badContentDetectionService): Response
    {
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardHabitFormAgainstBadContent($form, $habitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Habitude modifiee avec succes.');

            return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
        }

        return $this->render('admin/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => sprintf('Modifier habitude #%d', $habitude->getIdHabitude()),
            'form' => $form->createView(),
            'entity_label' => 'habitude',
            'back_route' => 'admin_gestion_suivi_habitudes_index',
        ]);
    }

    #[Route('/habitude/{idHabitude}/delete', name: 'habitude_delete', methods: ['POST'])]
    public function deleteHabitude(Request $request, Habitude $habitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_habitude_' . $habitude->getIdHabitude(), (string) $request->request->get('_token'))) {
            $entityManager->remove($habitude);
            $entityManager->flush();
            $this->addFlash('success', 'Habitude supprimee.');
        }

        return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
    }

    #[Route('/suivi/new', name: 'suivi_new', methods: ['GET', 'POST'])]
    public function newSuivi(Request $request, EntityManagerInterface $entityManager, SuivihabitudeRepository $suivihabitudeRepository, BadContentDetectionService $badContentDetectionService): Response
    {
        $suivi = new Suivihabitude();
        $suivi->setDate(new \DateTime('today'));
        $form = $this->createForm(SuivihabitudeType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardSuiviFormAgainstBadContent($form, $suivi, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $suivi->setIdSuivi($suivihabitudeRepository->nextId());
            $entityManager->persist($suivi);
            $entityManager->flush();
            $this->addFlash('success', 'Suivi ajoute avec succes.');

            return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
        }

        return $this->render('admin/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Nouveau suivi',
            'form' => $form->createView(),
            'entity_label' => 'suivi',
            'back_route' => 'admin_gestion_suivi_habitudes_index',
        ]);
    }

    #[Route('/suivi/{idSuivi}/edit', name: 'suivi_edit', methods: ['GET', 'POST'])]
    public function editSuivi(Request $request, Suivihabitude $suivihabitude, EntityManagerInterface $entityManager, BadContentDetectionService $badContentDetectionService): Response
    {
        $form = $this->createForm(SuivihabitudeType::class, $suivihabitude);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardSuiviFormAgainstBadContent($form, $suivihabitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Suivi modifie avec succes.');

            return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
        }

        return $this->render('admin/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => sprintf('Modifier suivi #%d', $suivihabitude->getIdSuivi()),
            'form' => $form->createView(),
            'entity_label' => 'suivi',
            'back_route' => 'admin_gestion_suivi_habitudes_index',
        ]);
    }

    #[Route('/suivi/{idSuivi}/delete', name: 'suivi_delete', methods: ['POST'])]
    public function deleteSuivi(Request $request, Suivihabitude $suivihabitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_suivi_' . $suivihabitude->getIdSuivi(), (string) $request->request->get('_token'))) {
            $entityManager->remove($suivihabitude);
            $entityManager->flush();
            $this->addFlash('success', 'Suivi supprime.');
        }

        return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
    }

    #[Route('/rappel/new', name: 'rappel_new', methods: ['GET', 'POST'])]
    public function newRappel(Request $request, EntityManagerInterface $entityManager, RappelHabitudeRepository $rappelHabitudeRepository, BadContentDetectionService $badContentDetectionService): Response
    {
        $rappel = new Rappel_habitude();
        $rappel->setCreatedAt(new \DateTime());
        $form = $this->createForm(RappelHabitudeType::class, $rappel);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardRappelFormAgainstBadContent($form, $rappel, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $rappel->setIdRappel($rappelHabitudeRepository->nextId());
            $entityManager->persist($rappel);
            $entityManager->flush();
            $this->addFlash('success', 'Rappel ajoute avec succes.');

            return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
        }

        return $this->render('admin/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => 'Nouveau rappel',
            'form' => $form->createView(),
            'entity_label' => 'rappel',
            'back_route' => 'admin_gestion_suivi_habitudes_index',
        ]);
    }

    #[Route('/rappel/{idRappel}/edit', name: 'rappel_edit', methods: ['GET', 'POST'])]
    public function editRappel(Request $request, Rappel_habitude $rappelHabitude, EntityManagerInterface $entityManager, BadContentDetectionService $badContentDetectionService): Response
    {
        $form = $this->createForm(RappelHabitudeType::class, $rappelHabitude);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->guardRappelFormAgainstBadContent($form, $rappelHabitude, $badContentDetectionService);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Rappel modifie avec succes.');

            return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
        }

        return $this->render('admin/gestion_suivi_habitudes/form.html.twig', [
            'page_title' => sprintf('Modifier rappel #%d', $rappelHabitude->getIdRappel()),
            'form' => $form->createView(),
            'entity_label' => 'rappel',
            'back_route' => 'admin_gestion_suivi_habitudes_index',
        ]);
    }

    #[Route('/rappel/{idRappel}/delete', name: 'rappel_delete', methods: ['POST'])]
    public function deleteRappel(Request $request, Rappel_habitude $rappelHabitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_rappel_' . $rappelHabitude->getIdRappel(), (string) $request->request->get('_token'))) {
            $entityManager->remove($rappelHabitude);
            $entityManager->flush();
            $this->addFlash('success', 'Rappel supprime.');
        }

        return $this->redirectToRoute('admin_gestion_suivi_habitudes_index');
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
