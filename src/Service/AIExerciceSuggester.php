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
     * Suggestion basée sur l'humeur (1-10)
     */
    public function suggestByMood(int $mood): ?Exercice
    {
        // Humeur très basse (1-3) -> exercices apaisants anti-anxiété
        if ($mood <= 3) {
            return $this->exerciceRepo->findOneBy(['nom' => '5-4-3-2-1 (retour au présent)'])
                ?? $this->exerciceRepo->findOneBy(['nom' => 'STOP (Arrêter la panique)'])
                ?? $this->exerciceRepo->findOneBy(['type' => 'Gestion de l\'anxiété']);
        }
        
        // Humeur moyenne (4-6) -> exercices d'ancrage ou respiration
        if ($mood <= 6) {
            return $this->exerciceRepo->findOneBy(['nom' => 'Respiration carrée (box breathing)'])
                ?? $this->exerciceRepo->findOneBy(['nom' => 'Ancrage par les pieds'])
                ?? $this->exerciceRepo->findOneBy(['type' => 'Respiration']);
        }
        
        // Bonne humeur (7-8) -> gratitude ou méditation légère
        if ($mood <= 8) {
            return $this->exerciceRepo->findOneBy(['nom' => '3 choses pour lesquelles je suis reconnaissant'])
                ?? $this->exerciceRepo->findOneBy(['type' => 'Gratitude']);
        }
        
        // Très bonne humeur (9-10) -> exercices dynamiques et joyeux
        return $this->exerciceRepo->findOneBy(['nom' => 'Danse de la victoire'])
            ?? $this->exerciceRepo->findOneBy(['type' => 'Bien-être']);
    }

    /**
     * Suggestion basée sur l'historique des exercices faits
     */
    public function suggestByHistory(Utilisateur $user): ?Exercice
    {
        // Dernier exercice fait par l'utilisateur
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true], 
            ['dateFin' => 'DESC']
        );
        
        if (!$lastSession || !$lastSession->getExercice()) {
            // Pas d'historique -> proposer un exercice facile
            return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE']);
        }
        
        $lastExercice = $lastSession->getExercice();
        $lastDifficulte = $lastExercice->getDifficulte();
        
        // Progression logique : augmenter la difficulté progressivement
        switch ($lastDifficulte) {
            case 'FACILE':
                return $this->exerciceRepo->findOneBy(['difficulte' => 'MOYEN']);
            case 'MOYEN':
                return $this->exerciceRepo->findOneBy(['difficulte' => 'DIFFICILE']);
            case 'DIFFICILE':
                // Alterner avec un exercice différent mais même niveau
                $otherExercice = $this->exerciceRepo->findOneBy([
                    'difficulte' => 'DIFFICILE',
                    'nom' => 'Rituel du matin'
                ]);
                if ($otherExercice && $otherExercice !== $lastExercice) {
                    return $otherExercice;
                }
                return $this->exerciceRepo->findOneBy(['difficulte' => 'MOYEN']);
            default:
                return $this->exerciceRepo->findOneBy(['difficulte' => 'FACILE']);
        }
    }

    /**
     * Suggestion combinée (humeur + historique)
     */
    public function suggestCombined(Utilisateur $user, int $mood): ?Exercice
    {
        // Priorité à l'humeur si elle est extrême (très basse)
        if ($mood <= 3) {
            return $this->suggestByMood($mood);
        }
        
        // Sinon, basé sur l'historique
        $historySuggestion = $this->suggestByHistory($user);
        
        // Éviter de proposer le même exercice 2 fois de suite
        $lastSession = $this->sessionRepo->findOneBy(
            ['user' => $user, 'terminee' => true],
            ['dateFin' => 'DESC']
        );
        
        if ($lastSession && $historySuggestion === $lastSession->getExercice()) {
            return $this->suggestByMood($mood);
        }
        
        return $historySuggestion;
    }

    /**
     * Suggestion simple sans historique (pour la page d'accueil)
     */
    public function getRandomSuggestion(): ?Exercice
    {
        $exercices = $this->exerciceRepo->findAll();
        
        if (empty($exercices)) {
            return null;
        }
        
        // Retourner un exercice aléatoire
        return $exercices[array_rand($exercices)];
    }
}