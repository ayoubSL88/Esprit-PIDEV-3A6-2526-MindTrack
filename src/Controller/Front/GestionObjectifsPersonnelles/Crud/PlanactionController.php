<?php

namespace App\Controller\Front\GestionObjectifsPersonnelles\Crud;

use App\Entity\Planaction;
use App\Form\PlanactionType;
use App\Repository\PlanactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/objectifs/plans-actions', name: 'front_planaction_')]
final class PlanactionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PlanactionRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('front/gestion_objectifs_personnelles/planaction/index.html.twig', [
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

    #[Route('/new', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, PlanactionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $plan = new Planaction();
        $plan->setIdPlan($repository->nextId());
        $plan->setPriorite(5);

        $form = $this->createForm(PlanactionType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($plan);
            $entityManager->flush();

            $this->addFlash('success', 'Plan d’action ajouté avec succès.');

            return $this->redirectToRoute('front_planaction_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/planaction/form.html.twig', [
            'form' => $form,
            'page_title' => 'Ajouter un plan d’action',
            'submit_label' => 'Créer',
        ]);
    }

    #[Route('/{idPlan}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $idPlan, Request $request, PlanactionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $plan = $repository->find($idPlan);

        if (!$plan instanceof Planaction) {
            throw $this->createNotFoundException('Plan d’action introuvable.');
        }

        $form = $this->createForm(PlanactionType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Plan d’action mis à jour avec succès.');

            return $this->redirectToRoute('front_planaction_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/planaction/form.html.twig', [
            'form' => $form,
            'page_title' => 'Modifier un plan d’action',
            'submit_label' => 'Enregistrer',
            'plan' => $plan,
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

        return $this->redirectToRoute('front_planaction_index');
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
