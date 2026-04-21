<?php

declare(strict_types=1);

namespace App\Service\Objectif;

use App\Entity\Objectif;
use App\Entity\Planificateurintelligent;
use App\Entity\Planaction;

final class SmartPlannerService
{
    /**
     * @param list<Planaction> $actions
     */
    public function buildPlan(Objectif $objectif, ?Planificateurintelligent $planner, array $actions): array
    {
        $start = \DateTimeImmutable::createFromInterface($objectif->getDateDebut());
        $end = \DateTimeImmutable::createFromInterface($objectif->getDateFin());
        $daysAvailable = max(1, (int) $start->diff($end)->format('%a') + 1);
        $capacity = max(1, $planner?->getCapaciteQuotidienne() ?? 1);

        usort(
            $actions,
            static fn (Planaction $left, Planaction $right): int => $right->getPriorite() <=> $left->getPriorite()
        );

        $schedule = [];
        $cursor = $start;
        $slotsUsed = 0;

        foreach ($actions as $action) {
            if ($slotsUsed >= $capacity) {
                $cursor = $cursor->modify('+1 day');
                $slotsUsed = 0;
            }

            if ($cursor > $end) {
                break;
            }

            $schedule[] = [
                'date' => $cursor->format('Y-m-d'),
                'action' => $action->getEtape(),
                'priority' => $action->getPriorite(),
            ];

            ++$slotsUsed;
        }

        return [
            'objectifId' => $objectif->getIdObj(),
            'objectif' => $objectif->getTitre(),
            'modeOrganisation' => $planner?->getModeOrganisation() ?? 'MANUEL',
            'capaciteQuotidienne' => $capacity,
            'daysAvailable' => $daysAvailable,
            'plannedActions' => $schedule,
        ];
    }
}
