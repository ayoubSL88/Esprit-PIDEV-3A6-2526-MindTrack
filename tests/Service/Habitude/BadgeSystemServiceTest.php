<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\BadgeSystemService;
use App\Service\Habitude\HabitCompletionService;
use App\Service\Habitude\HabitProgressService;
use App\Service\Habitude\HabitStreakService;
use PHPUnit\Framework\TestCase;

final class BadgeSystemServiceTest extends TestCase
{
    public function testEvaluateReturnsNoBadgeWhenThresholdsAreNotMet(): void
    {
        $completion = new HabitCompletionService();
        $progress = new HabitProgressService($completion);
        $streak = new HabitStreakService($completion);
        $service = new BadgeSystemService($progress, $streak);

        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-01')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-05-02')));

        self::assertSame([], $service->evaluate([$habit]));
    }

    public function testEvaluateReturnsExpectedBadgesWhenThresholdsAreMet(): void
    {
        $completion = new HabitCompletionService();
        $progress = new HabitProgressService($completion);
        $streak = new HabitStreakService($completion);
        $service = new BadgeSystemService($progress, $streak);
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        for ($day = 1; $day <= 30; ++$day) {
            $habit->addSuivihabitude(
                (new Suivihabitude())
                    ->setIdHabitude($habit)
                    ->setEtat(true)
                    ->setDate(new \DateTimeImmutable(sprintf('2026-04-%02d', $day)))
            );
        }

        $badges = $service->evaluate([$habit]);
        $codes = array_column($badges, 'badge');

        self::assertContains('REGULARITE_7_JOURS', $codes);
        self::assertContains('ENDURANCE_30_JOURS', $codes);
        self::assertContains('DISCIPLINE', $codes);
    }
}
