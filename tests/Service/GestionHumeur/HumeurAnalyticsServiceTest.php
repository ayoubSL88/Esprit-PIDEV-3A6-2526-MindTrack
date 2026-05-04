<?php

declare(strict_types=1);

namespace App\Tests\Service\GestionHumeur;

use App\Entity\Humeur;
use App\Service\GestionHumeur\HumeurAnalyticsService;
use PHPUnit\Framework\TestCase;

final class HumeurAnalyticsServiceTest extends TestCase
{
    public function testAnalyzeReturnsEmptyAnalysisWhenNoEntries(): void
    {
        $service = new HumeurAnalyticsService();

        $result = $service->analyze([]);

        self::assertSame('low', $result['riskLevel']);
        self::assertSame('unknown', $result['trendDirection']);
        self::assertSame([], $result['recommendations']);
    }

    public function testAnalyzeDetectsHighRiskForConsecutiveDifficultDays(): void
    {
        $service = new HumeurAnalyticsService();
        $entries = [
            $this->mood('2026-04-21', 'sad', 9),
            $this->mood('2026-04-22', 'stressed', 9),
            $this->mood('2026-04-23', 'sad', 8),
            $this->mood('2026-04-24', 'tired', 8),
        ];

        $result = $service->analyze($entries);

        self::assertSame('high', $result['riskLevel']);
        self::assertGreaterThanOrEqual(3, $result['currentStreak']);
        self::assertNotEmpty($result['alerts']);
    }

    private function mood(string $date, string $type, int $intensity): Humeur
    {
        $humeur = new Humeur();
        $humeur->setDate(new \DateTimeImmutable($date));
        $humeur->setTypeHumeur($type);
        $humeur->setIntensite($intensity);

        return $humeur;
    }
}

