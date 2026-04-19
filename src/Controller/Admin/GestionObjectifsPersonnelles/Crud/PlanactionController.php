<?php

namespace App\Controller\Admin\GestionObjectifsPersonnelles\Crud;

use App\Entity\Planaction;
use App\Repository\PlanactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dompdf\Dompdf;

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
    #[Route('/export/csv', name: 'export_csv', methods: ['GET'])]
public function exportCsv(PlanactionRepository $repository): Response
{
    $planactions = $repository->findAll();

    $response = new StreamedResponse(function () use ($planactions) {
        $handle = fopen('php://output', 'w+');

        // Header CSV
        fputcsv($handle, ['ID', 'Titre', 'Description', 'Date début', 'Date fin', 'Statut']);

        foreach ($planactions as $p) {
            fputcsv($handle, [
                $p->getId(),
                $p->getTitre(),
                $p->getDescription(),
                $p->getDateDebut()?->format('Y-m-d'),
                $p->getDateFin()?->format('Y-m-d'),
                $p->getStatut()
            ]);
        }

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="planactions.csv"');

    return $response;
}#[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
public function exportPdf(PlanactionRepository $repository): Response
{
    $planactions = $repository->findAll();

    $html = $this->renderView('admin/gestion_objectifs_personnelles/planaction/pdf.html.twig', [
        'planactions' => $planactions
    ]);

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response($dompdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="planactions.pdf"',
    ]);
}

    
}
