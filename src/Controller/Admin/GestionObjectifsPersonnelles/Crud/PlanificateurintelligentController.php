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
    public function index(
        Request $request,
        PlanificateurintelligentRepository $repository
    ): Response {
        $filters        = $this->getFilters($request);
        $planificateurs = $repository->findBySearchSortAndStatus(
            $filters['q'],
            $filters['sort'],
            $filters['status']
        );

        // ── Calcul des statistiques ──────────────────
        $tous           = $repository->findAll();
        $total          = count($tous);
        $flexible       = 0;
        $equilibre      = 0;
        $intensif       = 0;
        $sommeCapacite  = 0;
        $dernierDate    = null;

        foreach ($tous as $p) {
            $mode = $p->getModeOrganisation();
            if ($mode === 'flexible')  $flexible++;
            elseif ($mode === 'equilibre') $equilibre++;
            elseif ($mode === 'intensif')  $intensif++;

            $sommeCapacite += $p->getCapaciteQuotidienne();

            $gen = $p->getDerniereGeneration();
            if ($gen && (!$dernierDate || $gen > $dernierDate)) {
                $dernierDate = $gen;
            }
        }

        $capaciteMoyenne = $total > 0 ? round($sommeCapacite / $total, 1) : 0;
        $modeDominant    = 'aucun';
        $maxMode         = max($flexible, $equilibre, $intensif);
        if ($maxMode > 0) {
            if ($flexible  === $maxMode) $modeDominant = 'flexible';
            elseif ($equilibre === $maxMode) $modeDominant = 'equilibre';
            else $modeDominant = 'intensif';
        }
        // ─────────────────────────────────────────────

        return $this->render('admin/gestion_objectifs_personnelles/planificateurintelligent/index.html.twig', [
            'planificateurs' => $planificateurs,
            'filters'        => $filters,
            'sort_choices'   => [
                'Dernière génération récente' => 'date_desc',
                'Dernière génération ancienne' => 'date_asc',
                'Capacité croissante'          => 'capacite_asc',
                'Capacité décroissante'        => 'capacite_desc',
                'Statut A-Z'                   => 'statut_asc',
                'Statut Z-A'                   => 'statut_desc',
            ],
            'status_choices' => [
                'Flexible'  => 'flexible',
                'Equilibré' => 'equilibre',
                'Intensif'  => 'intensif',
            ],
            'stats' => [
                'total'           => $total,
                'flexible'        => $flexible,
                'equilibre'       => $equilibre,
                'intensif'        => $intensif,
                'capacite_moyenne'=> $capaciteMoyenne,
                'mode_dominant'   => $modeDominant,
                'derniere_gen'    => $dernierDate,
            ],
        ]);
    }

    #[Route('/{idPlanificateur}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $idPlanificateur,
        Request $request,
        PlanificateurintelligentRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $planificateur = $repository->find($idPlanificateur);

        if (!$planificateur instanceof Planificateurintelligent) {
            throw $this->createNotFoundException('Planificateur introuvable.');
        }

        if ($this->isCsrfTokenValid(
            'delete_planificateur_' . $planificateur->getIdPlanificateur(),
            (string) $request->request->get('_token')
        )) {
            $entityManager->remove($planificateur);
            $entityManager->flush();
            $this->addFlash('success', 'Planificateur supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_planificateurintelligent_index');
    }

    private function getFilters(Request $request): array
    {
        return [
            'q'      => trim((string) $request->query->get('q', '')),
            'sort'   => trim((string) $request->query->get('sort', 'date_desc')),
            'status' => trim((string) $request->query->get('status', '')),
        ];
    }
}