<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;

final class SmartReminderService
{
    public function suggest(Habitude $habitude, ?Rappel_habitude $existingReminder = null): array
    {
        $suggestedDays = match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => ['Lun'],
            'MENSUEL' => ['01'],
            default => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
        };

        $suggestedHour = $existingReminder?->getHeureRappel() ?? match ($habitude->getFrequence()) {
            'HEBDOMADAIRE' => '09:00',
            'MENSUEL' => '10:00',
            default => '08:00',
        };

        $message = sprintf(
            'Pense a realiser "%s" pour avancer vers %s.',
            $habitude->getNom(),
            $habitude->getObjectif()
        );

        $strategy = $existingReminder instanceof Rappel_habitude
            ? 'Conserver le rappel existant en l ajustant si le taux de reussite baisse.'
            : 'Creer un rappel simple au moment le plus facile a respecter.';

        return [
            'habitId' => $habitude->getIdHabitude(),
            'habitName' => $habitude->getNom(),
            'suggestedHour' => $suggestedHour,
            'suggestedDays' => $suggestedDays,
            'message' => $message,
            'strategy' => $strategy,
        ];
    }
}
