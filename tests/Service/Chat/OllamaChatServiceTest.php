<?php

declare(strict_types=1);

namespace App\Tests\Service\Chat;

use App\Service\Chat\OllamaChatService;
use PHPUnit\Framework\TestCase;

final class OllamaChatServiceTest extends TestCase
{
    public function testGenerateHabitReplyReturnsNullWhenServiceIsUnavailable(): void
    {
        $service = new OllamaChatService('http://127.0.0.1:9/api/chat', 'llama3.2:1b');

        self::assertNull($service->generateHabitReply(
            'Bonjour',
            [],
            [],
            [],
            [],
            'Reponse locale',
            []
        ));
    }
}
