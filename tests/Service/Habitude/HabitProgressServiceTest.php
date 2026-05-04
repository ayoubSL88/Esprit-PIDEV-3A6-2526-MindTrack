<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitCompletionService;
use App\Service\Habitude\HabitProgressService;
use PHPUnit\Framework\TestCase;

final class HabitProgressServiceTest extends TestCase
{
    public function testAnalyzeReturnsZeroRatesWhenNoTrackingExists(): void
    {
        $service = new HabitProgressService(new HabitCompletionService());
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setTargetValue(1)->setUnit('session');

        $result = $service->analyze($habit);

        self::assertSame(0, $result['trackedEntries']);
        self::assertSame(0.0, $result['completionRate']);
    }

    public function testAnalyzeComputesCompletionAndAverageValues(): void
    {
        $service = new HabitProgressService(new HabitCompletionService());
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Water')->setHabitType('NUMERIC')->setTargetValue(2)->setUnit('l');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setValeur(2));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setValeur(1));

        $result = $service->analyze($habit);

        self::assertSame(2, $result['trackedEntries']);
        self::assertSame(1, $result['completedEntries']);
        self::assertSame(50.0, $result['completionRate']);
        self::assertSame(1.5, $result['averageValue']);
    }
}

