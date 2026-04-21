<?php

namespace App\Controller\Admin\GestionObjectifsPersonnelles\Crud;

use App\Entity\Planaction;
use App\Repository\PlanactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/objectifs/plans-actions', name: 'admin_planaction_')]
final class PlanactionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PlanactionRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('admin/gestion_objectifs_personnelles/planaction/index.html.twig', [
            'plans' => $repository->findBySearchSortAndStatus($filters['q'], $filters['sort'], $filters['status']),
            'filters' => $filters,
            'sort_choices' => [
                'Priorité la plus forte' => 'priorite_desc',
                'Priorité la plus faible' => 'priorite_asc',
                'Plus ancien en premier' => 'date_asc',
                'Statut A-Z' => 'statut_asc',
                'Statut Z-A' => 'statut_desc',
            ],
            'status_choices' => [
                'Basse' => 'basse',
                'Moyenne' => 'moyenne',
                'Haute' => 'haute',
            ],
        ]);
    }

    #[Route('/{idPlan}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $idPlan, Request $request, PlanactionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $plan = $repository->find($idPlan);

        if (!$plan instanceof Planaction) {
            throw $this->createNotFoundException('Plan d’action introuvable.');
        }

        if ($this->isCsrfTokenValid('delete_plan_' . $plan->getIdPlan(), (string) $request->request->get('_token'))) {
            $entityManager->remove($plan);
            $entityManager->flush();
            $this->addFlash('success', 'Plan d’action supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_planaction_index');
    }

    private function getFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query->get('q', '')),
            'sort' => trim((string) $request->query->get('sort', 'priorite_desc')),
            'status' => trim((string) $request->query->get('status', '')),
        ];
    }
}
