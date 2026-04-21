<?php

namespace App\Service;

use App\Entity\Exercice;
use App\Entity\Utilisateur;
use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;

class AIExerciceSuggester
{
    private ExerciceRepository $exerciceRepo;
    private SessionRepository $sessionRepo;

    public function __construct(
        ExerciceRepository $exerciceRepo,
        SessionRepository $sessionRepo
    ) {
        $this->exerciceRepo = $exerciceRepo;
        $this->sessionRepo = $sessionRepo;
    }

    /**
     * Suggestion basee sur l humeur (1-10).
     * 1-3 => FACILE, 4-6 => MOYEN, 7-10 => DIFFICILE.
     */
    public function suggestByMood(int $mood): ?Exercice
    {
        if ($mood <= 3) {
            return $this->findRandomByDifficulty('FACILE')
                ?? $this->getRandomSuggestion();
        }

        if ($mood <= 6) {
            return $this->findRandomByDifficulty('MOYEN')
                ?? $this->findRandomByDifficulty('FACILE')
                ?? $this->getRandomSuggestion();
        }

        if ($mood <= 10) {
            return $this->findRandomByDifficulty('DIFFICILE')
                ?? $this->findRandomByDifficulty('MOYEN')
                ?? $this->getRandomSuggestion();
        }

        return $this->getRandomSuggestion();
    }

    public function suggestByHistory(Utilisateur $user): ?Exercice
    {
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true],
            ['dateFin' => 'DESC']
        );

        if (!$lastSession || !$lastSession->getExercice()) {
            return $this->findRandomByDifficulty('FACILE')
                ?? $this->getRandomSuggestion();
        }

        $lastExercice = $lastSession->getExercice();
        $lastDifficulte = $lastExercice->getDifficulte();

        switch ($lastDifficulte) {
            case 'FACILE':
                return $this->findRandomByDifficulty('MOYEN')
                    ?? $this->findRandomByDifficulty('FACILE')
                    ?? $this->getRandomSuggestion();
            case 'MOYEN':
                return $this->findRandomByDifficulty('DIFFICILE')
                    ?? $this->findRandomByDifficulty('MOYEN')
                    ?? $this->getRandomSuggestion();
            case 'DIFFICILE':
                return $this->findRandomByDifficulty('MOYEN')
                    ?? $this->findRandomByDifficulty('DIFFICILE')
                    ?? $this->getRandomSuggestion();
            default:
                return $this->findRandomByDifficulty('FACILE')
                    ?? $this->getRandomSuggestion();
        }
    }

    public function suggestCombined(Utilisateur $user, int $mood): ?Exercice
    {
        if ($mood <= 3) {
            return $this->suggestByMood($mood);
        }

        $historySuggestion = $this->suggestByHistory($user);
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true],
            ['dateFin' => 'DESC']
        );

        if ($lastSession && $historySuggestion && $historySuggestion->getIdEx() === $lastSession->getExercice()?->getIdEx()) {
            return $this->suggestByMood($mood);
        }

        return $historySuggestion ?? $this->suggestByMood($mood);
    }

    public function getRandomSuggestion(): ?Exercice
    {
        $exercices = $this->exerciceRepo->findAll();
        if ($exercices === []) {
            return null;
        }

        return $exercices[array_rand($exercices)];
    }

    private function findRandomByDifficulty(string $difficulty): ?Exercice
    {
        $matches = $this->exerciceRepo->findBy(['difficulte' => $difficulty]);
        if ($matches === []) {
            return null;
        }

        return $matches[array_rand($matches)];
    }
}
