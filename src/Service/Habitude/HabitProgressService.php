<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;

final class HabitProgressService
{
    public function __construct(
        private readonly HabitCompletionService $completionService,
    ) {
    }

    public function analyze(Habitude $habitude): array
    {
        $suivis = $habitude->getSuivihabitudes()->toArray();
        $total = count($suivis);
        $completed = 0;
        $totalRatio = 0.0;
        $totalValue = 0;

        /** @var Suivihabitude $suivi */
        foreach ($suivis as $suivi) {
            if ($this->completionService->isCompleted($suivi)) {
                ++$completed;
            }

            $totalRatio += $this->completionService->completionRatio($suivi);
            $totalValue += $suivi->getValeur();
        }

        $completionRate = $total > 0 ? round(($completed / $total) * 100, 2) : 0.0;
        $averageRatio = $total > 0 ? round(($totalRatio / $total) * 100, 2) : 0.0;
        $averageValue = $total > 0 ? round($totalValue / $total, 2) : 0.0;

        return [
            'habitId' => $habitude->getIdHabitude(),
            'habitName' => $habitude->getNom(),
            'trackedEntries' => $total,
            'completedEntries' => $completed,
            'completionRate' => $completionRate,
            'averageTargetReach' => $averageRatio,
            'averageValue' => $averageValue,
            'targetValue' => $habitude->getTargetValue(),
            'unit' => $habitude->getUnit(),
        ];
    }
}
