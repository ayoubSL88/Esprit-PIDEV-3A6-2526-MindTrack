<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;

final class HabitRecommendationService
{
    public function __construct(
        private readonly HabitProgressService $progressService,
        private readonly HabitRiskAnalyzerService $riskAnalyzerService,
        private readonly HabitStreakService $streakService,
    ) {
    }

    public function generate(Habitude $habitude): array
    {
        $progress = $this->progressService->analyze($habitude);
        $risk = $this->riskAnalyzerService->analyze($habitude);
        $streak = $this->streakService->analyze($habitude);

        $recommendations = [];

        if ($risk['riskLevel'] === 'ELEVE') {
            $recommendations[] = 'Reduire temporairement l objectif pour rendre l habitude plus facile a reprendre.';
            $recommendations[] = 'Ajouter un rappel actif et une verification quotidienne jusqu au retour a un rythme stable.';
        }

        if ($progress['completionRate'] < 50) {
            $recommendations[] = 'Commencer par une version minimale de l habitude pour augmenter le taux de reussite.';
        }

        if ($progress['averageTargetReach'] >= 100) {
            $recommendations[] = 'Augmenter legerement la cible ou ajouter une variante plus ambitieuse.';
        }

        if ($streak['currentStreak'] >= 7) {
            $recommendations[] = 'Maintenir la routine actuelle et ajouter une recompense ou un badge pour consolider la motivation.';
        }

        if ($recommendations === []) {
            $recommendations[] = 'Continuer sur le meme rythme et suivre la tendance sur les prochains jours.';
        }

        return [
            'habitId' => $habitude->getIdHabitude(),
            'habitName' => $habitude->getNom(),
            'recommendations' => $recommendations,
        ];
    }
}
