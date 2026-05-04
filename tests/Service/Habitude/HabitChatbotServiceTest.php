<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Service\Habitude\HabitChatbotService;
use PHPUnit\Framework\TestCase;

final class HabitChatbotServiceTest extends TestCase
{
    public function testBuildReplyReturnsOnboardingMessageWhenNoHabits(): void
    {
        $service = new HabitChatbotService();

        $result = $service->buildReply('bonjour', [], [], [], []);

        self::assertStringContainsString('pas encore d habitude', $result['reply']);
        self::assertNotEmpty($result['highlights']);
    }

    public function testBuildReplyReturnsProgressSummaryWhenAsked(): void
    {
        $service = new HabitChatbotService();
        $habit = (new Habitude())->setIdHabitude(1)->setNom('Lecture');
        $suivi = (new Suivihabitude())->setEtat(true)->setIdHabitude($habit);

        $result = $service->buildReply('donne moi un bilan de progression', [$habit], [], [$suivi], []);

        self::assertStringContainsString('1 habitude', $result['reply']);
        self::assertStringContainsString('1 suivi', $result['reply']);
    }
}

