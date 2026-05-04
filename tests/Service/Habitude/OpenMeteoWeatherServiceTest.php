<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Service\Habitude\OpenMeteoWeatherService;
use PHPUnit\Framework\TestCase;

final class OpenMeteoWeatherServiceTest extends TestCase
{
    public function testGetCurrentWeatherReturnsFallbackForInvalidCoordinates(): void
    {
        $service = new OpenMeteoWeatherService(9999.0, 9999.0, 'Nowhere');

        $result = $service->getCurrentWeather();

        self::assertArrayHasKey('available', $result);
        self::assertArrayHasKey('condition', $result);
        self::assertArrayHasKey('advice', $result);
    }

    public function testGetCurrentWeatherAlwaysReturnsConfiguredLocationLabel(): void
    {
        $service = new OpenMeteoWeatherService(36.8, 10.2, 'Tunis');

        $result = $service->getCurrentWeather();

        self::assertSame('Tunis', $result['locationLabel']);
    }
}

