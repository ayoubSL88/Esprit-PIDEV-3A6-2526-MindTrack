<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;

final class HabitStreakService
{
    public function __construct(
        private readonly HabitCompletionService $completionService,
    ) {
    }

    public function analyze(Habitude $habitude, ?\DateTimeInterface $today = null): array
    {
        $today ??= new \DateTimeImmutable('today');
        $completedDays = $this->completionService->completedDaysForHabit($habitude);
        $completedPeriods = $this->uniqueCompletedPeriods($habitude, $completedDays);

        if ($completedPeriods === []) {
            return [
                'habitId' => $habitude->getIdHabitude(),
                'habitName' => $habitude->getNom(),
                'currentStreak' => 0,
                'bestStreak' => 0,
                'lastCompletedAt' => null,
                'completedDays' => 0,
            ];
        }

        $bestStreak = 0;
        $currentWindow = 0;
        $previous = null;

        foreach ($completedPeriods as $periodDate) {
            $current = new \DateTimeImmutable($periodDate);

            if ($previous !== null && $this->isNextPeriod($habitude, $previous, $current)) {
                ++$currentWindow;
            } else {
                $currentWindow = 1;
            }

            $bestStreak = max($bestStreak, $currentWindow);
            $previous = $current;
        }

        $currentStreak = $this->calculateCurrentStreak(
            $habitude,
            $completedPeriods,
            \DateTimeImmutable::createFromInterface($today),
        );

        return [
            'habitId' => $habitude->getIdHabitude(),
            'habitName' => $habitude->getNom(),
            'currentStreak' => $currentStreak,
            'bestStreak' => $bestStreak,
            'lastCompletedAt' => end($completedDays) ?: null,
            'completedDays' => count($completedDays),
        ];
    }

    /**
     * @param list<string> $completedDays
     *
     * @return list<string>
     */
    private function uniqueCompletedPeriods(Habitude $habitude, array $completedDays): array
    {
        $periods = [];

        foreach ($completedDays as $day) {
            $periodStart = $this->normalizeToPeriodStart($habitude, new \DateTimeImmutable($day));
            $periods[$periodStart->format('Y-m-d')] = true;
        }

        ksort($periods);

        return array_keys($periods);
    }

    /**
     * @param list<string> $completedPeriods
     */
    private function calculateCurrentStreak(
        Habitude $habitude,
        array $completedPeriods,
        \DateTimeImmutable $today,
    ): int {
        if ($completedPeriods === []) {
            return 0;
        }

        $completedIndex = array_flip($completedPeriods);

        // Fix : on utilise la date du dernier suivi comme référence
        // au lieu de "aujourd'hui", ce qui permet aux dates futures
        // d'être comptées sans casser le streak
        $latestCompletedPeriod = new \DateTimeImmutable((string) end($completedPeriods));

        $cursor = $latestCompletedPeriod;
        $currentStreak = 0;

        while (isset($completedIndex[$cursor->format('Y-m-d')])) {
            ++$currentStreak;
            $cursor = $this->moveToPreviousPeriod($habitude, $cursor);
        }

        return $currentStreak;
    }

    private function isNextPeriod(Habitude $habitude, \DateTimeImmutable $previous, \DateTimeImmutable $current): bool
    {
        return $this->moveToNextPeriod($habitude, $previous)->format('Y-m-d') === $current->format('Y-m-d');
    }

    private function normalizeToPeriodStart(Habitude $habitude, \DateTimeImmutable $date): \DateTimeImmutable
    {
        return match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => $date->modify('monday this week'),
            'MENSUEL' => $date->modify('first day of this month'),
            default => $date,
        };
    }

    private function moveToPreviousPeriod(Habitude $habitude, \DateTimeImmutable $date): \DateTimeImmutable
    {
        return match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => $date->modify('-1 week'),
            'MENSUEL' => $date->modify('first day of previous month'),
            default => $date->modify('-1 day'),
        };
    }

    private function moveToNextPeriod(Habitude $habitude, \DateTimeImmutable $date): \DateTimeImmutable
    {
        return match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => $date->modify('+1 week'),
            'MENSUEL' => $date->modify('first day of next month'),
            default => $date->modify('+1 day'),
        };
    }
}