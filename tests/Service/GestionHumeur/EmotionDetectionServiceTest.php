<?php

declare(strict_types=1);

namespace App\Tests\Service\GestionHumeur;

use App\Service\GestionHumeur\EmotionDetectionService;
use PHPUnit\Framework\TestCase;

final class EmotionDetectionServiceTest extends TestCase
{
    public function testDetectFromDataUrisThrowsWhenNoFramesProvided(): void
    {
        $service = new EmotionDetectionService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No camera frames were captured.');
        $service->detectFromDataUris([]);
    }

    public function testDetectFromDataUriThrowsForInvalidFormat(): void
    {
        $service = new EmotionDetectionService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The captured image format is invalid.');
        $service->detectFromDataUri('invalid-data-uri');
    }
}

