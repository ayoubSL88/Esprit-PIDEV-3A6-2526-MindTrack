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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
    
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
public function create(
    Request $request,
    PlanactionRepository $repository,
    EntityManagerInterface $entityManager,
    MailerInterface $mailer
): Response {
    $plan = new Planaction();
    $plan->setIdPlan($repository->nextId());
    $plan->setPriorite(5);

    $form = $this->createForm(PlanactionType::class, $plan);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($plan);
        $entityManager->flush();

        // Envoi email direct
        $email = (new Email())
            ->from('barhoumia093@gmail.com')
            ->to('barhoumia093@gmail.com')
            ->subject('Nouveau plan d\'action cree - MindTrack')
            ->html('
                <div style="font-family:Arial,sans-serif; max-width:500px; margin:0 auto;">
                    <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6); padding:24px; text-align:center; border-radius:12px 12px 0 0;">
                        <h1 style="color:white; margin:0; font-size:1.2rem;">Nouveau plan d\'action</h1>
                    </div>
                    <div style="padding:24px; background:white;">
                        <p style="color:#64748b;">Un nouveau plan d\'action a ete cree :</p>
                        <div style="background:#f5f3ff; border-left:4px solid #6366f1; border-radius:8px; padding:16px; margin:16px 0;">
                            <p style="margin:0; font-weight:600; color:#4338ca;">' . $plan->getEtape() . '</p>
                            <p style="margin:6px 0 0; font-size:0.85rem; color:#6366f1;">Priorite : ' . $plan->getPriorite() . '/10</p>
                        </div>
                    </div>
                    <div style="background:#f8fafc; padding:12px; text-align:center; font-size:0.75rem; color:#94a3b8; border-radius:0 0 12px 12px;">
                        MindTrack
                    </div>
                </div>
            ');

        $mailer->send($email);

        $this->addFlash('success', 'Plan d\'action ajoute et email envoye.');

        return $this->redirectToRoute('front_planaction_index');
    }

    return $this->render('front/gestion_objectifs_personnelles/planaction/form.html.twig', [
        'form' => $form,
        'page_title' => 'Ajouter un plan d\'action',
        'submit_label' => 'Creer',
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
