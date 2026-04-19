<?php

namespace App\Controller\Front\GestionObjectifsPersonnelles\Crud;

use App\Entity\Objectif;
use App\Entity\Jalonprogression;
use App\Entity\Planaction;
use App\Form\ObjectifType;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\JalonprogressionRepository;
use App\Repository\PlanactionRepository;


#[Route('/app/objectifs/objectif', name: 'front_objectif_')]
final class ObjectifController extends AbstractController
{
    // ── SHOW (pour QR Code et visualisation) ───────────────────────
   #[Route('/{idObj<\d+>}/show', name: 'show', methods: ['GET'])]
public function show(
    int $idObj,
    ObjectifRepository $objectifRepository,
    JalonprogressionRepository $jalonRepo,
    PlanactionRepository $planRepo
): Response {

    // 1. Récupération objectif
    $objectif = $objectifRepository->find($idObj);

    if (!$objectif) {
        throw $this->createNotFoundException('Objectif introuvable.');
    }

    // 2. Récupération des données liées
    $jalons = $jalonRepo->findBy(['idObj' => $objectif]);
    $plans  = $planRepo->findBy(['idObj' => $objectif]);

    // 3. Calcul progression
    $total = count($jalons);
    $faits = 0;

    foreach ($jalons as $jalon) {
        if ($jalon->getAtteint()) {
            $faits++;
        }
    }

    $progression = $total > 0
        ? round(($faits / $total) * 100)
        : 0;

    // 4. Render view
    return $this->render(
        'front/gestion_objectifs_personnelles/objectif/show.html.twig',
        [
            'objectif'    => $objectif,
            'jalons'      => $jalons,
            'plans'       => $plans,
            'progression' => $progression,
        ]
    );
}
    // ── INDEX AVEC PAGINATION ─────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        ObjectifRepository $repository,
        PaginatorInterface $paginator
    ): Response {
        $filters = $this->getFilters($request);

        $query = $repository->findBySearchSortAndStatusQuery(
            $filters['q'],
            $filters['sort'],
            $filters['status']
        );

        $objectifs = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('front/gestion_objectifs_personnelles/objectif/index.html.twig', [
            'objectifs'      => $objectifs,
            'filters'        => $filters,
            'sort_choices'   => [
                'Plus récents'          => 'date_desc',
                'Plus anciens'          => 'date_asc',
                'Fin la plus proche'    => 'fin_asc',
                'Fin la plus lointaine' => 'fin_desc',
                'Statut A-Z'            => 'statut_asc',
                'Statut Z-A'            => 'statut_desc',
            ],
            'status_choices' => [
                'A faire'  => 'a_faire',
                'En cours' => 'en_cours',
                'Terminé'  => 'termine',
                'Annulé'   => 'annule',
            ],
        ]);
    }

    // ── CREATE ────────────────────────────────────────────────────
    #[Route('/new', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        ObjectifRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $objectif = new Objectif();
        $objectif->setIdObj($repository->nextId());
        $objectif->setDateDebut(new \DateTime());
        $objectif->setDateFin((new \DateTime())->modify('+30 days'));
        $objectif->setStatut('a_faire');

        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($objectif);
            $entityManager->flush();

            $this->addFlash('success', 'Objectif ajouté avec succès.');

            return $this->redirectToRoute('front_objectif_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/objectif/form.html.twig', [
            'form'         => $form,
            'page_title'   => 'Ajouter un objectif',
            'submit_label' => 'Créer',
        ]);
    }

    // ── EDIT ──────────────────────────────────────────────────────
    #[Route('/{idObj}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $idObj,
        Request $request,
        ObjectifRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $objectif = $repository->find($idObj);

        if (!$objectif instanceof Objectif) {
            throw $this->createNotFoundException('Objectif introuvable.');
        }

        $form = $this->createForm(ObjectifType::class, $objectif);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Objectif mis à jour avec succès.');
            return $this->redirectToRoute('front_objectif_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/objectif/form.html.twig', [
            'form'         => $form,
            'page_title'   => 'Modifier un objectif',
            'submit_label' => 'Enregistrer',
            'objectif'     => $objectif,
        ]);
    }

    // ── DELETE ────────────────────────────────────────────────────
    #[Route('/{idObj}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $idObj,
        Request $request,
        ObjectifRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $objectif = $repository->find($idObj);

        if (!$objectif instanceof Objectif) {
            throw $this->createNotFoundException('Objectif introuvable.');
        }

        if ($this->isCsrfTokenValid('delete_objectif_' . $objectif->getIdObj(), (string) $request->request->get('_token'))) {
            $entityManager->remove($objectif);
            $entityManager->flush();
            $this->addFlash('success', 'Objectif supprimé avec succès.');
        }

        return $this->redirectToRoute('front_objectif_index');
    }

    // ── DUPLIQUER ─────────────────────────────────────────────────
    #[Route('/{idObj}/dupliquer', name: 'dupliquer', methods: ['POST'])]
    public function dupliquer(
        int $idObj,
        ObjectifRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $original = $repository->find($idObj);

        if (!$original instanceof Objectif) {
            throw $this->createNotFoundException('Objectif introuvable.');
        }

        $clone = new Objectif();
        $clone->setIdObj($repository->nextId());
        $clone->setTitre($original->getTitre() . ' (copie)');
        $clone->setDescriprion($original->getDescriprion());
        $clone->setStatut('a_faire');
        $clone->setDateDebut(new \DateTime());
        $clone->setDateFin((new \DateTime())->modify('+30 days'));

        $entityManager->persist($clone);
        $entityManager->flush();

        $this->addFlash('success', 'Objectif dupliqué avec succès.');

        return $this->redirectToRoute('front_objectif_index');
    }

    // ── ARCHIVER ──────────────────────────────────────────────────
    #[Route('/{idObj}/archiver', name: 'archiver', methods: ['POST'])]
    public function archiver(
        int $idObj,
        ObjectifRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $objectif = $repository->find($idObj);

        if (!$objectif instanceof Objectif) {
            throw $this->createNotFoundException('Objectif introuvable.');
        }

        $objectif->setStatut('annule');
        $entityManager->flush();

        $this->addFlash('success', 'Objectif archivé.');

        return $this->redirectToRoute('front_objectif_index');
    }

    // ── PROGRESSION API ───────────────────────────────────────────
    #[Route('/{idObj}/progression', name: 'progression', methods: ['GET'])]
   public function progression(
    int $idObj,
    ObjectifRepository $repository,
    EntityManagerInterface $em
): JsonResponse
     {
        $objectif = $repository->find($idObj);

        if (!$objectif instanceof Objectif) {
            throw $this->createNotFoundException('Objectif introuvable.');
        }

       $jalons = $em
    ->getRepository(\App\Entity\Jalonprogression::class)
    ->findBy(['idObj' => $objectif]);
        
        $total = count($jalons);
        $faits = 0;

        foreach ($jalons as $jalon) {
            if ($jalon->getAtteint()) $faits++;
        }

        $progression = $total > 0 ? round(($faits / $total) * 100) : 0;

        return $this->json([
            'idObj'            => $objectif->getIdObj(),
            'titre'            => $objectif->getTitre(),
            'progression'      => $progression,
            'jalons_total'     => $total,
            'jalons_completes' => $faits,
            'jalons_restants'  => $total - $faits,
            'statut'           => $objectif->getStatut(),
            'date_fin'         => $objectif->getDateFin()?->format('Y-m-d'),
        ]);
    }

    // ── EXPORT PDF ────────────────────────────────────────────────
    #[Route('/{idObj}/export-pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdf(
    int $idObj,
    ObjectifRepository $repository,
    EntityManagerInterface $em
    ): Response {
        $objectif = $repository->find($idObj);

        if (!$objectif instanceof Objectif) {
            throw $this->createNotFoundException('Objectif introuvable.');
        }

        $jalons = $em
    ->getRepository(\App\Entity\Jalonprogression::class)
    ->findBy(['idObj' => $objectif]);

$planactions = $em
    ->getRepository(\App\Entity\Planaction::class)
    ->findBy(['idObj' => $objectif]);
        
        $total = count($jalons);
        $faits = 0;
        foreach ($jalons as $jalon) {
            if ($jalon->getAtteint()) $faits++;
        }
        $progression = $total > 0 ? round(($faits / $total) * 100) : 0;

        $html = $this->renderView(
            'front/gestion_objectifs_personnelles/objectif/rapport_pdf.html.twig',
            [
                'objectif'    => $objectif,
                'jalons'      => $jalons,
                'planactions' => $planactions,
                'progression' => $progression,
                'date'        => new \DateTime(),
            ]
        );

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="objectif-' . $idObj . '-' . date('Y-m-d') . '.pdf"',
            ]
        );
    }

    // ── CALENDAR API ──────────────────────────────────────────────
    #[Route('/calendar', name: 'calendar', methods: ['GET'])]
    public function calendar(ObjectifRepository $repository): JsonResponse
    {
        $objectifs = $repository->findAll();
        $data      = [];

        foreach ($objectifs as $objectif) {
            $data[] = [
                'id'     => $objectif->getIdObj(),
                'title'  => $objectif->getTitre(),
                'start'  => $objectif->getDateDebut()?->format('Y-m-d'),
                'end'    => $objectif->getDateFin()?->format('Y-m-d'),
                'status' => $objectif->getStatut(),
            ];
        }

        return $this->json($data);
    }

    // ── STATS API ─────────────────────────────────────────────────
    #[Route('/api/stats', name: 'api_stats', methods: ['GET'])]
    public function apiStats(
    ObjectifRepository $repository,
    EntityManagerInterface $em ): JsonResponse
    {
        $objectifs = $repository->findAll();
        $now       = new \DateTime();
        $total     = count($objectifs);
        $termines  = 0;
        $enRetard  = 0;
        $enCours   = 0;
        $progressions = [];

        foreach ($objectifs as $obj) {
            $statut  = $obj->getStatut();
            $dateFin = $obj->getDateFin();

            if ($statut === 'termine') $termines++;
            elseif ($dateFin && $dateFin < $now && $statut !== 'termine') $enRetard++;
            else $enCours++;

           $jalons = $em
    ->getRepository(\App\Entity\Jalonprogression::class)
    ->findBy(['idObj' => $obj]);
            $t = count($jalons);
            $f = 0;
            foreach ($jalons as $j) { 
                if ($j->getAtteint()) $f++; 
            }
            if ($t > 0) $progressions[] = round(($f / $t) * 100);
        }

        return $this->json([
            'total'               => $total,
            'en_cours'            => $enCours,
            'termines'            => $termines,
            'en_retard'           => $enRetard,
            'progression_moyenne' => count($progressions) > 0
                ? round(array_sum($progressions) / count($progressions))
                : 0,
        ]);
    }

    // ── EVENTS CALENDAR API ───────────────────────────────────────
    #[Route('/api/events', name: 'api_events', methods: ['GET'])]
    public function apiEvents(ObjectifRepository $repository): JsonResponse
    {
        $objectifs = $repository->findAll();
        $events    = [];
        $colors    = [
            'a_faire'  => '#378ADD',
            'en_cours' => '#EF9F27',
            'termine'  => '#639922',
            'annule'   => '#888780',
        ];

        foreach ($objectifs as $obj) {
            if (!$obj->getDateFin()) continue;
            $events[] = [
                'id'    => $obj->getIdObj(),
                'title' => $obj->getTitre(),
                'start' => $obj->getDateDebut()?->format('Y-m-d'),
                'end'   => $obj->getDateFin()->format('Y-m-d'),
                'color' => $colors[$obj->getStatut()] ?? '#534AB7',
                'url'   => $this->generateUrl('front_objectif_edit', ['idObj' => $obj->getIdObj()]),
            ];
        }

        return $this->json($events);
    }

    // ── FILTERS ───────────────────────────────────────────────────
    private function getFilters(Request $request): array
    {
        return [
            'q'      => trim((string) $request->query->get('q', '')),
            'sort'   => trim((string) $request->query->get('sort', 'date_desc')),
            'status' => trim((string) $request->query->get('status', '')),
        ];
    }
}