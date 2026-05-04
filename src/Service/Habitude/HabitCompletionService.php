<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;

final class HabitCompletionService
{
    public function isCompleted(Suivihabitude $suivi): bool
    {
        $habitude = $suivi->getIdHabitude();

        if ($habitude->getHabitType() === 'NUMERIC') {
            $valeur = $suivi->getValeur();

            return $valeur >= $habitude->getTargetValue();
        }

        return $suivi->getEtat();
    }

    public function completionRatio(Suivihabitude $suivi): float
    {
        $habitude = $suivi->getIdHabitude();

        if ($habitude->getHabitType() === 'NUMERIC') {
            $target = max(1, $habitude->getTargetValue());

            return min(1.0, $suivi->getValeur() / $target);
        }

        return $suivi->getEtat() ? 1.0 : 0.0;
    }

    public function completedDaysForHabit(Habitude $habitude): array
    {
        $days = [];

        foreach ($habitude->getSuivihabitudes() as $suivi) {
            if (!$this->isCompleted($suivi)) {
                continue;
            }

            $days[$suivi->getDate()->format('Y-m-d')] = true;
        }

        ksort($days);

        return array_keys($days);
    }
}
