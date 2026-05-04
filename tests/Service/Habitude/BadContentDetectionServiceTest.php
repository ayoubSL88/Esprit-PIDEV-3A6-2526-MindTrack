<?php

declare(strict_types=1);

namespace App\Tests\Service\Habitude;

use App\Service\Habitude\BadContentDetectionService;
use PHPUnit\Framework\TestCase;

final class BadContentDetectionServiceTest extends TestCase
{
    public function testAnalyzeAllowsEmptyMessage(): void
    {
        $service = new BadContentDetectionService(null);

        $result = $service->analyze('   ');

        self::assertFalse($result['blocked']);
        self::assertSame([], $result['categories']);
    }

    public function testAnalyzeBlocksViolentMessageWithHeuristicFallback(): void
    {
        $service = new BadContentDetectionService(null);

        $result = $service->analyze('I want to kill people now');

        self::assertTrue($result['blocked']);
        self::assertContains('violence', $result['categories']);
    }
}

