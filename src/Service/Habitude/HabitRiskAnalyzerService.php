<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;

final class HabitRiskAnalyzerService
{
    public function __construct(
        private readonly HabitCompletionService $completionService,
        private readonly HabitProgressService $progressService,
    ) {
    }

    public function analyze(Habitude $habitude, ?\DateTimeInterface $today = null): array
    {
        $today ??= new \DateTimeImmutable('today');
        $progress = $this->progressService->analyze($habitude);
        $suivis = $habitude->getSuivihabitudes()->toArray();

        usort(
            $suivis,
            static fn (Suivihabitude $left, Suivihabitude $right): int => ($left->getDate()?->getTimestamp() ?? 0) <=> ($right->getDate()?->getTimestamp() ?? 0)
        );

        $failedInRow = 0;
        for ($index = count($suivis) - 1; $index >= 0; --$index) {
            if ($this->completionService->isCompleted($suivis[$index])) {
                break;
            }

            ++$failedInRow;
        }

        $lastSuivi = $suivis !== [] ? $suivis[array_key_last($suivis)] : null;
        $lastTrackedAt = $lastSuivi?->getDate();
        $daysSinceLastTrack = $lastTrackedAt instanceof \DateTimeInterface
            ? (int) \DateTimeImmutable::createFromInterface($lastTrackedAt)->diff(\DateTimeImmutable::createFromInterface($today))->format('%a')
            : null;

        $riskScore = 0;

        if ($progress['completionRate'] < 40) {
            $riskScore += 3;
        } elseif ($progress['completionRate'] < 70) {
            $riskScore += 2;
        } else {
            ++$riskScore;
        }

        if ($failedInRow >= 3) {
            $riskScore += 3;
        } elseif ($failedInRow >= 1) {
            ++$riskScore;
        }

        $warningThreshold = $this->warningThresholdInDays($habitude);
        $criticalThreshold = $this->criticalThresholdInDays($habitude);

        if (($daysSinceLastTrack ?? 0) >= $criticalThreshold) {
            $riskScore += 3;
        } elseif (($daysSinceLastTrack ?? 0) >= $warningThreshold) {
            $riskScore += 2;
        }

        $level = match (true) {
            $riskScore >= 7 => 'ELEVE',
            $riskScore >= 4 => 'MOYEN',
            default => 'FAIBLE',
        };

        return [
            'habitId' => $habitude->getIdHabitude(),
            'habitName' => $habitude->getNom(),
            'riskLevel' => $level,
            'riskScore' => $riskScore,
            'failedInRow' => $failedInRow,
            'daysSinceLastTrack' => $daysSinceLastTrack,
            'completionRate' => $progress['completionRate'],
        ];
    }

    private function warningThresholdInDays(Habitude $habitude): int
    {
        return match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => 10,
            'MENSUEL' => 40,
            default => 3,
        };
    }

    private function criticalThresholdInDays(Habitude $habitude): int
    {
        return match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => 21,
            'MENSUEL' => 75,
            default => 7,
        };
    }
}
