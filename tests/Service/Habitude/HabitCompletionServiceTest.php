<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitCompletionService;
use PHPUnit\Framework\TestCase;

final class HabitCompletionServiceTest extends TestCase
{
    public function testIsCompletedForNumericHabitUsesTargetComparison(): void
    {
        $service = new HabitCompletionService();
        $habit = (new Habitude())->setHabitType('NUMERIC')->setTargetValue(3);
        $suivi = (new Suivihabitude())->setIdHabitude($habit)->setValeur(2)->setEtat(true);

        self::assertFalse($service->isCompleted($suivi));
        self::assertSame(2 / 3, $service->completionRatio($suivi));
    }

    public function testCompletedDaysForHabitReturnsUniqueSortedCompletedDates(): void
    {
        $service = new HabitCompletionService();
        $habit = (new Habitude())->setHabitType('BOOLEAN')->setTargetValue(1);
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-03')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-05-01')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-02')));

        self::assertSame(['2026-05-02', '2026-05-03'], $service->completedDaysForHabit($habit));
    }
}

