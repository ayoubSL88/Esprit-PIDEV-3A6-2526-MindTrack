<?php

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Repository\RappelHabitudeRepository;
use App\Repository\SuivihabitudeRepository;

final class HabitReminderDueResolver
{
    public function __construct(
        private readonly RappelHabitudeRepository $rappelHabitudeRepository,
        private readonly SuivihabitudeRepository $suivihabitudeRepository,
        private readonly HabitCompletionService $habitCompletionService,
    ) {
    }

    /**
     * @return list<Rappel_habitude>
     */
    public function resolveDueAt(\DateTimeImmutable $now): array
    {
        $currentMinute = $now->format('H:i');

        return array_values(array_filter(
            $this->rappelHabitudeRepository->findActiveWithHabitAndOwner(),
            fn(Rappel_habitude $reminder): bool => $this->isDueNow($reminder, $now, $currentMinute)
        ));
    }

    private function isDueNow(Rappel_habitude $reminder, \DateTimeImmutable $now, string $currentMinute): bool
    {
        $habit = $reminder->getIdHabitude();
        if (!$habit instanceof Habitude || $habit->getIdU() === null) {
            return false;
        }

        if ($reminder->getHeureRappel() !== $currentMinute) {
            return false;
        }

        if (!$this->matchesDay($reminder->getJours(), $now)) {
            return false;
        }

        if ($reminder->getLastSentAt()?->format('Y-m-d H:i') === $now->format('Y-m-d H:i')) {
            return false;
        }

        if ($this->isAlreadyCompletedToday($habit, $now)) {
            return false;
        }

        return true;
    }

    private function matchesDay(string $days, \DateTimeImmutable $now): bool
    {
        $normalized = $this->normalize($days);
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['tous', 'touslesjours', 'quotidien', 'daily', '*'], true)) {
            return true;
        }

        $tokens = preg_split('/[\s,;\/|]+/', $normalized) ?: [];
        $allowed = array_values(array_filter(array_map($this->normalize(...), $tokens)));
        $currentDay = match ($now->format('N')) {
            '1' => 'lun',
            '2' => 'mar',
            '3' => 'mer',
            '4' => 'jeu',
            '5' => 'ven',
            '6' => 'sam',
            default => 'dim',
        };

        foreach ($allowed as $token) {
            $mapped = match ($token) {
                'lundi', 'monday', 'mon' => 'lun',
                'mardi', 'tuesday', 'tue' => 'mar',
                'mercredi', 'wednesday', 'wed' => 'mer',
                'jeudi', 'thursday', 'thu' => 'jeu',
                'vendredi', 'friday', 'fri' => 'ven',
                'samedi', 'saturday', 'sat' => 'sam',
                'dimanche', 'sunday', 'sun' => 'dim',
                default => $token,
            };

            if ($mapped === $currentDay) {
                return true;
            }
        }

        return false;
    }

    private function isAlreadyCompletedToday(Habitude $habit, \DateTimeImmutable $now): bool
    {
        foreach ($this->suivihabitudeRepository->findForHabitOnDate($habit, $now) as $suivi) {
            if ($this->habitCompletionService->isCompleted($suivi)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));

        return str_replace(
            ['é', 'è', 'ê', 'à', 'ù', 'û', 'ô', 'î', 'ï', 'ç'],
            ['e', 'e', 'e', 'a', 'u', 'u', 'o', 'i', 'i', 'c'],
            $value
        );
    }
}
