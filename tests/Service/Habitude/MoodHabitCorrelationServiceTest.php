<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Humeur;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitCompletionService;
use App\Service\Habitude\MoodHabitCorrelationService;
use PHPUnit\Framework\TestCase;

final class MoodHabitCorrelationServiceTest extends TestCase
{
    public function testAnalyzeReturnsNullDifferenceWithoutSamples(): void
    {
        $service = new MoodHabitCorrelationService(new HabitCompletionService());
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run');

        $result = $service->analyze($habit, []);

        self::assertNull($result['difference']);
        self::assertSame(0, $result['samplesWithHabit']);
    }

    public function testAnalyzeComputesDifferenceBetweenCompletedAndIncompleteDays(): void
    {
        $service = new MoodHabitCorrelationService(new HabitCompletionService());
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Run')->setHabitType('BOOLEAN');

        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(true)->setDate(new \DateTimeImmutable('2026-05-01')));
        $habit->addSuivihabitude((new Suivihabitude())->setIdHabitude($habit)->setEtat(false)->setDate(new \DateTimeImmutable('2026-05-02')));

        $mood1 = new Humeur();
        $mood1->setDate(new \DateTimeImmutable('2026-05-01'));
        $mood1->setTypeHumeur('happy');
        $mood1->setIntensite(8);

        $mood2 = new Humeur();
        $mood2->setDate(new \DateTimeImmutable('2026-05-02'));
        $mood2->setTypeHumeur('sad');
        $mood2->setIntensite(2);

        $result = $service->analyze($habit, [$mood1, $mood2]);

        self::assertSame(8.0, $result['averageMoodWithHabit']);
        self::assertSame(2.0, $result['averageMoodWithoutHabit']);
        self::assertSame(6.0, $result['difference']);
    }
}

