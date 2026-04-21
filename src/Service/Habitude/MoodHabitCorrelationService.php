<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Humeur;
use App\Entity\Suivihabitude;

final class MoodHabitCorrelationService
{
    public function __construct(
        private readonly HabitCompletionService $completionService,
    ) {
    }

    /**
     * @param list<Humeur> $humeurs
     */
    public function analyze(Habitude $habitude, array $humeurs): array
    {
        $moodByDay = [];

        foreach ($humeurs as $humeur) {
            $moodByDay[$humeur->getDate()->format('Y-m-d')][] = $humeur;
        }

        $withHabit = [];
        $withoutHabit = [];

        /** @var Suivihabitude $suivi */
        foreach ($habitude->getSuivihabitudes() as $suivi) {
            if ($suivi->getDate() === null) {
                continue;
            }

            $day = $suivi->getDate()->format('Y-m-d');
            $moods = $moodByDay[$day] ?? [];

            if ($moods === []) {
                continue;
            }

            $bucket = $this->completionService->isCompleted($suivi) ? 'withHabit' : 'withoutHabit';

            foreach ($moods as $mood) {
                if ($bucket === 'withHabit') {
                    $withHabit[] = $mood->getIntensite();
                } else {
                    $withoutHabit[] = $mood->getIntensite();
                }
            }
        }

        $averageWith = $withHabit !== [] ? round(array_sum($withHabit) / count($withHabit), 2) : null;
        $averageWithout = $withoutHabit !== [] ? round(array_sum($withoutHabit) / count($withoutHabit), 2) : null;

        return [
            'habitId' => $habitude->getIdHabitude(),
            'habitName' => $habitude->getNom(),
            'averageMoodWithHabit' => $averageWith,
            'averageMoodWithoutHabit' => $averageWithout,
            'difference' => $averageWith !== null && $averageWithout !== null ? round($averageWith - $averageWithout, 2) : null,
            'samplesWithHabit' => count($withHabit),
            'samplesWithoutHabit' => count($withoutHabit),
        ];
    }
}
