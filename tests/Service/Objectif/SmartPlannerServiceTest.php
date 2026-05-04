<?php

declare(strict_types=1);

namespace App\Tests\Service\Objectif;

use App\Entity\Objectif;
use App\Entity\Planaction;
use App\Entity\Planificateurintelligent;
use App\Service\Objectif\SmartPlannerService;
use PHPUnit\Framework\TestCase;

final class SmartPlannerServiceTest extends TestCase
{
    public function testBuildPlanSortsActionsByPriorityAndSchedulesWithinCapacity(): void
    {
        $service = new SmartPlannerService();
        $objectif = $this->objectif('2026-05-01', '2026-05-03');
        $planner = new Planificateurintelligent();
        $planner->setModeOrganisation('equilibre');
        $planner->setCapaciteQuotidienne(1);
        $actions = [
            $this->action('Low', 1),
            $this->action('High', 3),
            $this->action('Mid', 2),
        ];

        $result = $service->buildPlan($objectif, $planner, $actions);

        self::assertSame('High', $result['plannedActions'][0]['action']);
        self::assertSame('Mid', $result['plannedActions'][1]['action']);
        self::assertSame(3, $result['daysAvailable']);
    }

    public function testBuildPlanUsesManualDefaultsWithoutPlanner(): void
    {
        $service = new SmartPlannerService();
        $objectif = $this->objectif('2026-05-01', '2026-05-01');
        $actions = [$this->action('Only', 2)];

        $result = $service->buildPlan($objectif, null, $actions);

        self::assertSame('MANUEL', $result['modeOrganisation']);
        self::assertSame(1, $result['capaciteQuotidienne']);
        self::assertCount(1, $result['plannedActions']);
    }

    private function objectif(string $start, string $end): Objectif
    {
        $objectif = new Objectif();
        $objectif->setIdObj(1);
        $objectif->setTitre('Goal');
        $objectif->setDateDebut(new \DateTimeImmutable($start));
        $objectif->setDateFin(new \DateTimeImmutable($end));
        $objectif->setDescriprion('Desc');
        $objectif->setStatut('a_faire');

        return $objectif;
    }

    private function action(string $step, int $priority): Planaction
    {
        $action = new Planaction();
        $action->setEtape($step);
        $action->setPriorite($priority);

        return $action;
    }
}
