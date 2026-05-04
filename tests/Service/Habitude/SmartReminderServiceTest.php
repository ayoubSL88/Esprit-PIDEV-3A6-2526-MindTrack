<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Service\Habitude\SmartReminderService;
use PHPUnit\Framework\TestCase;

final class SmartReminderServiceTest extends TestCase
{
    public function testSuggestUsesFrequencyDefaultsWhenNoReminderExists(): void
    {
        $service = new SmartReminderService();
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Read')->setObjectif('Finish one chapter')->setFrequence('HEBDOMADAIRE');

        $result = $service->suggest($habit);

        self::assertSame('09:00', $result['suggestedHour']);
        self::assertSame(['Lun'], $result['suggestedDays']);
    }

    public function testSuggestKeepsExistingReminderHourWhenProvided(): void
    {
        $service = new SmartReminderService();
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Read')->setObjectif('Finish one chapter')->setFrequence('QUOTIDIEN');
        $rappel = (new Rappel_habitude())->setHeureRappel('18:30');

        $result = $service->suggest($habit, $rappel);

        self::assertSame('18:30', $result['suggestedHour']);
        self::assertStringContainsString('Conserver le rappel existant', $result['strategy']);
    }
}

