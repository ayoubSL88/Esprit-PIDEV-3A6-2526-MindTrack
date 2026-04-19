<?php

namespace App\Controller\Front\GestionObjectifsPersonnelles\Crud;

use App\Entity\Planificateurintelligent;
use App\Form\PlanificateurintelligentType;
use App\Repository\PlanificateurintelligentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\AIPlanificateurService;

#[Route('/app/objectifs/planificateurs', name: 'front_planificateurintelligent_')]
final class PlanificateurintelligentController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PlanificateurintelligentRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('front/gestion_objectifs_personnelles/planificateurintelligent/index.html.twig', [
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

    #[Route('/new', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, PlanificateurintelligentRepository $repository, EntityManagerInterface $entityManager, AIPlanificateurService $aiService): Response
    {
        $planificateur = new Planificateurintelligent();
        $planificateur->setIdPlanificateur($repository->nextId());
        $planificateur->setModeOrganisation('equilibre');
        $planificateur->setCapaciteQuotidienne(8);
        $planificateur->setDerniereGeneration(new \DateTimeImmutable());

        $form = $this->createForm(PlanificateurintelligentType::class, $planificateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $genererIA = $form->get('genererIA')->getData();
            $objectif = $planificateur->getIdObj();
            
            if ($genererIA && $objectif) {
                $iaResult = $aiService->generateCompletePlan(
                    $objectif->getTitre(),
                    $objectif->getDescriprion() ?? ''
                );
                
                $planificateur->setModeOrganisation($iaResult['mode_organisation']);
                $planificateur->setCapaciteQuotidienne($iaResult['capacite_quotidienne']);
                $entityManager->persist($planificateur);
                $entityManager->flush();
                
                $this->addFlash('success', '✅ Planificateur IA créé ! Mode: ' . $iaResult['mode_organisation'] . ', Capacité: ' . $iaResult['capacite_quotidienne'] . 'h');
                
                foreach ($iaResult['conseils'] as $conseil) {
                    $this->addFlash('info', '💡 ' . $conseil);
                }
            } else {
                $entityManager->persist($planificateur);
                $entityManager->flush();
                $this->addFlash('success', 'Planificateur ajouté avec succès.');
            }
            
            return $this->redirectToRoute('front_planificateurintelligent_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/planificateurintelligent/form.html.twig', [
            'form' => $form,
            'page_title' => 'Ajouter un planificateur intelligent',
            'submit_label' => 'Créer',
        ]);
    }

    #[Route('/{idPlanificateur}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $idPlanificateur, Request $request, PlanificateurintelligentRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $planificateur = $repository->find($idPlanificateur);

        if (!$planificateur instanceof Planificateurintelligent) {
            throw $this->createNotFoundException('Planificateur introuvable.');
        }

        $form = $this->createForm(PlanificateurintelligentType::class, $planificateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Planificateur mis à jour avec succès.');

            return $this->redirectToRoute('front_planificateurintelligent_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/planificateurintelligent/form.html.twig', [
            'form' => $form,
            'page_title' => 'Modifier un planificateur intelligent',
            'submit_label' => 'Enregistrer',
            'planificateur' => $planificateur,
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

        return $this->redirectToRoute('front_planificateurintelligent_index');
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