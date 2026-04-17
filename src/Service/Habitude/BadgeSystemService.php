<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;

final class BadgeSystemService
{
    public function __construct(
        private readonly HabitProgressService $progressService,
        private readonly HabitStreakService $streakService,
    ) {
    }

    /**
     * @param list<Habitude> $habitudes
     */
    public function evaluate(array $habitudes): array
    {
        $badges = [];

        foreach ($habitudes as $habitude) {
            $progress = $this->progressService->analyze($habitude);
            $streak = $this->streakService->analyze($habitude);

            if ($streak['bestStreak'] >= 7) {
                $badges[] = [
                    'habitName' => $habitude->getNom(),
                    'badge' => 'REGULARITE_7_JOURS',
                    'reason' => $streak['currentStreak'] >= 7
                        ? 'Serie de 7 jours ou plus en cours.'
                        : 'Serie de 7 jours ou plus deja atteinte.',
                ];
            }

            if ($streak['bestStreak'] >= 30) {
                $badges[] = [
                    'habitName' => $habitude->getNom(),
                    'badge' => 'ENDURANCE_30_JOURS',
                    'reason' => 'Meilleure serie de 30 jours ou plus.',
                ];
            }

            if ($progress['completionRate'] >= 80 && $progress['trackedEntries'] >= 10) {
                $badges[] = [
                    'habitName' => $habitude->getNom(),
                    'badge' => 'DISCIPLINE',
                    'reason' => 'Taux de reussite d au moins 80% sur un volume significatif.',
                ];
            }
        }

        return $badges;
    }
}
