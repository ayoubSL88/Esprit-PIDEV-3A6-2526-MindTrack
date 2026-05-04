<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitCompletionService;
use App\Service\Habitude\HabitProgressService;
use App\Service\Habitude\HabitRiskAnalyzerService;
use PHPUnit\Framework\TestCase;

final class HabitRiskAnalyzerServiceTest extends TestCase
{
    public function testAnalyzeReturnsLowRiskForStrongCompletion(): void
    {
        $service = new HabitRiskAnalyzerService(
            new HabitCompletionService(),
            new HabitProgressService(new HabitCompletionService())
        );
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-01')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-02')));

        $result = $service->analyze($habit, new \DateTimeImmutable('2026-05-03'));

        self::assertSame('FAIBLE', $result['riskLevel']);
    }

    public function testAnalyzeReturnsHighRiskForFailuresAndLongGap(): void
    {
        $service = new HabitRiskAnalyzerService(
            new HabitCompletionService(),
            new HabitProgressService(new HabitCompletionService())
        );
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-04-20')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-04-21')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-04-22')));

        $result = $service->analyze($habit, new \DateTimeImmutable('2026-05-04'));

        self::assertSame('ELEVE', $result['riskLevel']);
        self::assertGreaterThanOrEqual(3, $result['failedInRow']);
    }
}

