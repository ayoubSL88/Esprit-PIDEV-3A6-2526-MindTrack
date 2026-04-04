<?php

namespace App\Controller\Admin\GestionObjectifsPersonnelles\Crud;

use App\Entity\Planificateurintelligent;
use App\Repository\PlanificateurintelligentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/objectifs/planificateurs', name: 'admin_planificateurintelligent_')]
final class PlanificateurintelligentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PlanificateurintelligentRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('admin/gestion_objectifs_personnelles/planificateurintelligent/index.html.twig', [
            'planificateurs' => $repository->findBySearchSortAndStatus($filters['q'], $filters['sort'], $filters['status']),
            'filters' => $filters,
            'sort_choices' => [
                'Dernière génération récente' => 'date_desc',
                'Dernière génération ancienne' => 'date_asc',
                'Capacité croissante' => 'capacite_asc',
                'Capacité décroissante' => 'capacite_desc',
                'Statut A-Z' => 'statut_asc',
                'Statut Z-A' => 'statut_desc',
            ],
            'status_choices' => [
                'Flexible' => 'flexible',
                'Equilibré' => 'equilibre',
                'Intensif' => 'intensif',
            ],
        ]);
    }

    #[Route('/{idPlanificateur}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $idPlanificateur, Request $request, PlanificateurintelligentRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $planificateur = $repository->find($idPlanificateur);

        if (!$planificateur instanceof Planificateurintelligent) {
            throw $this->createNotFoundException('Planificateur introuvable.');
        }

        if ($this->isCsrfTokenValid('delete_planificateur_' . $planificateur->getIdPlanificateur(), (string) $request->request->get('_token'))) {
            $entityManager->remove($planificateur);
            $entityManager->flush();
            $this->addFlash('success', 'Planificateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_planificateurintelligent_index');
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
