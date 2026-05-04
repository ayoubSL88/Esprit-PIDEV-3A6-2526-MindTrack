<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitCompletionService;
use App\Service\Habitude\HabitRecommendationService;
use PHPUnit\Framework\TestCase;

final class HabitRecommendationServiceTest extends TestCase
{
    public function testGenerateReturnsFallbackRecommendationWhenMetricsAreStable(): void
    {
        $completion = new HabitCompletionService();
        $progress = new \App\Service\Habitude\HabitProgressService($completion);
        $risk = new \App\Service\Habitude\HabitRiskAnalyzerService($completion, $progress);
        $streak = new \App\Service\Habitude\HabitStreakService($completion);
        $service = new HabitRecommendationService($progress, $risk, $streak);
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-01')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-02')));

        $result = $service->generate($habit);

        self::assertCount(1, $result['recommendations']);
        self::assertStringNotContainsString('Reduire temporairement', $result['recommendations'][0]);
    }

    public function testGenerateReturnsMultipleRecommendationsForHighRiskContext(): void
    {
        $completion = new HabitCompletionService();
        $progress = new \App\Service\Habitude\HabitProgressService($completion);
        $risk = new \App\Service\Habitude\HabitRiskAnalyzerService($completion, $progress);
        $streak = new \App\Service\Habitude\HabitStreakService($completion);
        $service = new HabitRecommendationService($progress, $risk, $streak);
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setFrequence('QUOTIDIEN')->setHabitType('BOOLEAN');
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-04-20')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-04-21')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-04-22')));

        $result = $service->generate($habit);

        self::assertGreaterThanOrEqual(2, count($result['recommendations']));
        self::assertStringContainsString('Reduire temporairement', $result['recommendations'][0]);
    }
}
