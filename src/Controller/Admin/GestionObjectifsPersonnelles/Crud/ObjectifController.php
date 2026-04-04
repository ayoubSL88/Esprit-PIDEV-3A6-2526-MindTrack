<?php

namespace App\Controller\Admin\GestionObjectifsPersonnelles\Crud;

use App\Entity\Objectif;
use App\Repository\ObjectifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/objectifs/objectif', name: 'admin_objectif_')]
final class ObjectifController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ObjectifRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('admin/gestion_objectifs_personnelles/objectif/index.html.twig', [
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

        return $this->redirectToRoute('admin_objectif_index');
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
