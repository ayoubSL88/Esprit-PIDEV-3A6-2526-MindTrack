<?php

declare(strict_types=1);

namespace App\Tests\Service\Chat;

use App\Service\Chat\OllamaChatService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OllamaChatServiceTest extends TestCase
{
    public function testGenerateHabitReplyReturnsNullWhenServiceIsUnavailable(): void
    {
        $service = new OllamaChatService(
            'http://127.0.0.1:9/api/chat',
            'llama3.2:1b',
            new MockHttpClient([
                new MockResponse('', ['http_code' => 503]),
                new MockResponse('', ['http_code' => 503]),
            ])
        );

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

    public function testGenerateHabitReplyFallsBackToInstalledModel(): void
    {
        $service = new OllamaChatService(
            'http://127.0.0.1:11434/api/chat',
            'llama3.1',
            new MockHttpClient([
                new MockResponse('{"error":"model not found"}', ['http_code' => 404]),
                new MockResponse('{"models":[{"name":"llama3.2:1b"}]}', ['http_code' => 200]),
                new MockResponse('{"message":{"content":"Salut depuis Ollama"}}', ['http_code' => 200]),
            ])
        );

        $reply = $service->generateHabitReply('Bonjour', [], [], [], [], 'Reponse locale', []);

        self::assertSame('Salut depuis Ollama', $reply['reply'] ?? null);
        self::assertSame('ollama', $reply['source'] ?? null);
        self::assertSame('llama3.2:1b', $reply['model'] ?? null);
    }

    public function testGetStatusReturnsResolvedModelAndInstalledModels(): void
    {
        $service = new OllamaChatService(
            'http://127.0.0.1:11434',
            '',
            new MockHttpClient([
                new MockResponse('{"models":[{"name":"llama3.2:1b"},{"name":"phi3:latest"}]}', ['http_code' => 200]),
                new MockResponse('{"models":[{"name":"llama3.2:1b"},{"name":"phi3:latest"}]}', ['http_code' => 200]),
            ])
        );

        $status = $service->getStatus();

        self::assertTrue($status['enabled']);
        self::assertTrue($status['online']);
        self::assertSame('http://127.0.0.1:11434/api/chat', $status['configuredUrl']);
        self::assertSame('llama3.2:1b', $status['resolvedModel']);
        self::assertSame(['llama3.2:1b', 'phi3:latest'], $status['installedModels']);
    }
}
