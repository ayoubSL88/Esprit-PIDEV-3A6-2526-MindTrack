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

    public function suggestByMood(int $mood, ?Utilisateur $user = null): ?Exercice
    {
        // Mapping humeur → ID_EX (basé sur votre BDD)
        $mapping = [
            1 => 22,   // 5-4-3-2-1 (retour au présent)
            2 => 18,   // STOP (outil de pause mentale)
            3 => 14,   // Relaxation
            4 => 16,   // Scan corporel guidé (auto-administré)
            5 => 20,   // Détente musculaire séquentielle
            6 => 19,   // La roue des émotions – identification précise
            7 => 15,   // Les 3 bonnes choses du jour
            8 => 17,   // Journal de l’auto-compassion
            9 => 21,   // Trois preuves de compétence
            10 => 23,  // Micro-focus 5-1
        ];
        
        $idEx = $mapping[$mood] ?? 14;
        $exercice = $this->exerciceRepo->find($idEx);
        
        if (!$exercice) {
            return $this->exerciceRepo->findOneBy([]);
        }
        
        return $exercice;
    }

    public function suggestByHistory(Utilisateur $user): ?Exercice
    {
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true], 
            ['dateFin' => 'DESC']
        );
        
        if (!$lastSession || !$lastSession->getExercice()) {
            return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE']);
        }
        
        $lastDifficulte = $lastSession->getExercice()->getDifficulte();
        
        switch ($lastDifficulte) {
            case 'FACILE':
                return $this->exerciceRepo->findOneBy(['difficulte' => 'MOYEN']);
            case 'MOYEN':
                return $this->exerciceRepo->findOneBy(['difficulte' => 'DIFFICILE']);
            case 'DIFFICILE':
                $other = $this->exerciceRepo->findOneBy(['difficulte' => 'DIFFICILE']);
                return $other ?? $this->exerciceRepo->findOneBy(['difficulte' => 'MOYEN']);
            default:
                return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE']);
        }
    }

    public function suggestCombined(Utilisateur $user, int $mood): ?Exercice
    {
        if ($mood <= 3 || $mood >= 9) {
            return $this->suggestByMood($mood, $user);
        }
        return $this->suggestByHistory($user);
    }

    public function getRandomSuggestion(): ?Exercice
    {
        $exercices = $this->exerciceRepo->findAll();
        return empty($exercices) ? null : $exercices[array_rand($exercices)];
    }
}