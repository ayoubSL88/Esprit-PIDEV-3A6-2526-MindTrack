<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitCompletionService;
use App\Service\Habitude\HabitStreakService;
use PHPUnit\Framework\TestCase;

final class HabitStreakServiceTest extends TestCase
{
    public function testAnalyzeReturnsZeroStreakWithoutCompletedDays(): void
    {
        $service = new HabitStreakService(new HabitCompletionService());
        $habit = (new Habitude())->setIdHabitude(10)->setNom('Read')->setFrequence('QUOTIDIEN');

        $result = $service->analyze($habit);

        self::assertSame(0, $result['currentStreak']);
        self::assertSame(0, $result['bestStreak']);
    }

    public function testAnalyzeComputesCurrentAndBestStreakForDailyHabit(): void
    {
        $service = new HabitStreakService(new HabitCompletionService());
        $habit = (new Habitude())->setIdHabitude(10)->setNom('Read')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-01')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-02')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-03')));

        $result = $service->analyze($habit);

        self::assertSame(3, $result['currentStreak']);
        self::assertSame(3, $result['bestStreak']);
    }
}

