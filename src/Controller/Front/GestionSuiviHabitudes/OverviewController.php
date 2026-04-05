<?php

namespace App\Controller\Front\GestionSuiviHabitudes;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Entity\Suivihabitude;
use App\Form\Admin\GestionSuiviHabitudes\HabitudeType;
use App\Form\Admin\GestionSuiviHabitudes\RappelHabitudeType;
use App\Form\Admin\GestionSuiviHabitudes\SuivihabitudeType;
use App\Repository\HabitudeRepository;
use App\Repository\RappelHabitudeRepository;
use App\Repository\SuivihabitudeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    ): Response {
        $habitudeFilters = [
            'q' => (string) $request->query->get('q', ''),
            'frequence' => (string) $request->query->get('frequence', ''),
            'habitType' => (string) $request->query->get('type', ''),
            'sort' => (string) $request->query->get('sort', 'nom'),
            'direction' => (string) $request->query->get('direction', ''),
        ];

        $habitudes = $habitudeRepository->findAdminList($habitudeFilters);
        $suivis = $suivihabitudeRepository->findAdminList([
            'sort' => 'date',
            'direction' => 'DESC',
        ]);
        $rappels = $rappelHabitudeRepository->findAdminList([
            'sort' => 'heure',
            'direction' => 'ASC',
            'actif' => '1',
        ]);

        $today = new \DateTimeImmutable('today');
        $todayKey = $today->format('Y-m-d');

        $todaySuivis = array_values(array_filter(
            $suivis,
            static fn (Suivihabitude $suivi): bool => $suivi->getDate()?->format('Y-m-d') === $todayKey
        ));

        $recentSuivis = array_slice($suivis, 0, 6);
        $todayRappels = array_slice($rappels, 0, 6);

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
            ]);
        }

        return $this->render('front/gestion_suivi_habitudes/index.html.twig', [
            'habitudes' => $habitudes,
            'todaySuivis' => $todaySuivis,
            'recentSuivis' => $recentSuivis,
            'todayRappels' => $todayRappels,
            'notificationRappels' => $notificationRappels,
            'filters' => $habitudeFilters,
            'stats' => [
                'habitudes' => $habitudeRepository->countAll(),
                'rappels_actifs' => $rappelHabitudeRepository->countActive(),
                'suivis_completed' => $suivihabitudeRepository->countCompleted(),
                'suivis_total' => $suivihabitudeRepository->countAll(),
            ],
        ]);
    }

    #[Route('/habitude/new', name: 'habitude_new', methods: ['GET', 'POST'])]
    public function newHabitude(Request $request, EntityManagerInterface $entityManager, HabitudeRepository $habitudeRepository): Response
    {
        $habitude = new Habitude();
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $habitude->setIdHabitude($habitudeRepository->nextId());
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
    public function editHabitude(Request $request, Habitude $habitude, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

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
    public function deleteHabitude(Request $request, Habitude $habitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_habitude_' . $habitude->getIdHabitude(), (string) $request->request->get('_token'))) {
            $entityManager->remove($habitude);
            $entityManager->flush();
            $this->addFlash('success', 'Habitude supprimee.');
        }

        return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
    }

    #[Route('/suivi/new', name: 'suivi_new', methods: ['GET', 'POST'])]
    public function newSuivi(Request $request, EntityManagerInterface $entityManager, SuivihabitudeRepository $suivihabitudeRepository): Response
    {
        $suivi = new Suivihabitude();
        $suivi->setDate(new \DateTime('today'));
        $form = $this->createForm(SuivihabitudeType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $suivi->setIdSuivi($suivihabitudeRepository->nextId());
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
    public function editSuivi(Request $request, Suivihabitude $suivihabitude, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SuivihabitudeType::class, $suivihabitude);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
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
    public function deleteSuivi(Request $request, Suivihabitude $suivihabitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_suivi_' . $suivihabitude->getIdSuivi(), (string) $request->request->get('_token'))) {
            $entityManager->remove($suivihabitude);
            $entityManager->flush();
            $this->addFlash('success', 'Suivi supprime.');
        }

        return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
    }

    #[Route('/rappel/new', name: 'rappel_new', methods: ['GET', 'POST'])]
    public function newRappel(Request $request, EntityManagerInterface $entityManager, RappelHabitudeRepository $rappelHabitudeRepository): Response
    {
        $rappel = new Rappel_habitude();
        $rappel->setCreatedAt(new \DateTime());
        $form = $this->createForm(RappelHabitudeType::class, $rappel);
        $form->handleRequest($request);

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
    public function editRappel(Request $request, Rappel_habitude $rappelHabitude, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RappelHabitudeType::class, $rappelHabitude);
        $form->handleRequest($request);

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
    public function deleteRappel(Request $request, Rappel_habitude $rappelHabitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_rappel_' . $rappelHabitude->getIdRappel(), (string) $request->request->get('_token'))) {
            $entityManager->remove($rappelHabitude);
            $entityManager->flush();
            $this->addFlash('success', 'Rappel supprime.');
        }

        return $this->redirectToRoute('front_gestion_suivi_habitudes_index');
    }
}
