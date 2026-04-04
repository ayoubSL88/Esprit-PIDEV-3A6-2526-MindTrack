<?php

namespace App\Controller\Front\GestionObjectifsPersonnelles\Crud;

use App\Entity\Objectif;
use App\Form\ObjectifType;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/objectifs/objectif', name: 'front_objectif_')]
final class ObjectifController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ObjectifRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('front/gestion_objectifs_personnelles/objectif/index.html.twig', [
            'objectifs' => $repository->findBySearchSortAndStatus($filters['q'], $filters['sort'], $filters['status']),
            'filters' => $filters,
            'sort_choices' => [
                'Plus récents' => 'date_desc',
                'Plus anciens' => 'date_asc',
                'Fin la plus proche' => 'fin_asc',
                'Fin la plus lointaine' => 'fin_desc',
                'Statut A-Z' => 'statut_asc',
                'Statut Z-A' => 'statut_desc',
            ],
            'status_choices' => [
                'A faire' => 'a_faire',
                'En cours' => 'en_cours',
                'Terminé' => 'termine',
                'Annulé' => 'annule',
            ],
        ]);
    }

    #[Route('/new', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, ObjectifRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $objectif = new Objectif();
        $objectif->setIdObj($repository->nextId());
        $objectif->setDateDebut(new \DateTimeImmutable());
        $objectif->setDateFin((new \DateTimeImmutable())->modify('+30 days'));
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
            'form' => $form,
            'page_title' => 'Ajouter un objectif',
            'submit_label' => 'Créer',
        ]);
    }

    #[Route('/{idObj}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $idObj, Request $request, ObjectifRepository $repository, EntityManagerInterface $entityManager): Response
    {
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
            'form' => $form,
            'page_title' => 'Modifier un objectif',
            'submit_label' => 'Enregistrer',
            'objectif' => $objectif,
        ]);
    }

    #[Route('/{idObj}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $idObj, Request $request, ObjectifRepository $repository, EntityManagerInterface $entityManager): Response
    {
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

    private function getFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query->get('q', '')),
            'sort' => trim((string) $request->query->get('sort', 'date_desc')),
            'status' => trim((string) $request->query->get('status', '')),
        ];
    }
}
