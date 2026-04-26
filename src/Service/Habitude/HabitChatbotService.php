<?php

declare(strict_types=1);

namespace App\Service\Habitude;

use App\Entity\Habitude;
use App\Entity\Rappel_habitude;
use App\Entity\Suivihabitude;

final class HabitChatbotService
{
    /**
     * @param list<Habitude> $habitudes
     * @param list<Rappel_habitude> $rappels
     * @param list<Suivihabitude> $todaySuivis
     * @param array<int, array<string, mixed>> $advancedInsights
     * @return array{reply: string, highlights: list<string>}
     */
    public function buildReply(string $message, array $habitudes, array $rappels, array $todaySuivis, array $advancedInsights): array
    {
        $normalized = $this->normalize($message);
        $totalHabits = count($habitudes);
        $completedToday = count(array_filter($todaySuivis, static fn (Suivihabitude $suivi): bool => $suivi->getEtat()));

        if ($totalHabits === 0) {
            return [
                'reply' => 'Vous n avez pas encore d habitude enregistree. Creez une premiere habitude simple puis ajoutez un rappel pour installer le rythme.',
                'highlights' => [
                    'Commencer par une habitude tres facile',
                    'Programmer un rappel a une heure realiste',
                ],
            ];
        }

        $matchedHabit = $this->findHabitMention($normalized, $habitudes);
        if ($matchedHabit instanceof Habitude) {
            $insight = $advancedInsights[$matchedHabit->getIdHabitude()] ?? null;
            if (is_array($insight)) {
                $completionRate = (float) ($insight['progress']['completionRate'] ?? 0);
                $riskLevel = strtolower((string) ($insight['risk']['riskLevel'] ?? 'faible'));
                $recommendations = $insight['recommendation']['recommendations'] ?? [];

                return [
                    'reply' => sprintf(
                        'Pour %s, le taux de completion est de %s%% et le niveau de risque est %s. La meilleure prochaine action est de %s.',
                        $matchedHabit->getNom(),
                        number_format($completionRate, 0, ',', ' '),
                        $riskLevel,
                        $recommendations[0] ?? 'continuer avec un rythme regulier'
                    ),
                    'highlights' => array_slice(array_map('strval', $recommendations), 0, 2),
                ];
            }
        }

        if ($this->containsAny($normalized, ['risque', 'bloque', 'retard', 'difficile', 'probleme'])) {
            $criticalHabits = $this->extractHabitsByRisk($advancedInsights, 'ELEVE');

            return [
                'reply' => $criticalHabits !== []
                    ? sprintf('Les habitudes les plus fragiles en ce moment sont %s. Reduisez temporairement leur difficulte et remettez un rappel actif.', implode(', ', $criticalHabits))
                    : 'Je ne vois pas d habitude en risque eleve pour le moment. Le plus utile est de garder un rythme regulier sur vos habitudes actuelles.',
                'highlights' => [
                    'Reprendre par une version minimale',
                    'Verifier les rappels actifs',
                ],
            ];
        }

        if ($this->containsAny($normalized, ['progres', 'progression', 'resume', 'bilan', 'stat'])) {
            $bestHabit = $this->findBestHabit($advancedInsights);

            return [
                'reply' => sprintf(
                    'Vous suivez %d habitude(s) et vous avez complete %d suivi(s) aujourd hui. %s',
                    $totalHabits,
                    $completedToday,
                    $bestHabit !== null
                        ? sprintf('Votre meilleure dynamique actuelle concerne %s.', $bestHabit)
                        : 'Je peux detailler une habitude precise si vous voulez.'
                ),
                'highlights' => [
                    sprintf('%d habitude(s) active(s)', $totalHabits),
                    sprintf('%d suivi(s) complete(s) aujourd hui', $completedToday),
                ],
            ];
        }

        if ($this->containsAny($normalized, ['rappel', 'notification', 'oublie'])) {
            return [
                'reply' => sprintf(
                    'Vous avez %d rappel(s) actif(s). Si une habitude est souvent oubliee, placez le rappel juste avant le moment ou vous pouvez reellement la faire.',
                    count($rappels)
                ),
                'highlights' => [
                    'Choisir une heure realiste',
                    'Prioriser les habitudes fragiles',
                ],
            ];
        }

        $stableHabits = $this->extractHabitsByRisk($advancedInsights, 'FAIBLE');

        return [
            'reply' => sprintf(
                'Vous avez %d habitude(s) actives et %d suivi(s) completes aujourd hui. %s Je peux aussi analyser une habitude precise si vous ecrivez son nom.',
                $totalHabits,
                $completedToday,
                $stableHabits !== [] ? sprintf('Les habitudes les plus stables sont %s.', implode(', ', array_slice($stableHabits, 0, 3))) : 'Aucune habitude n est encore totalement stable.'
            ),
            'highlights' => [
                'Demander ma progression',
                'Demander les habitudes a risque',
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $advancedInsights
     * @return list<string>
     */
    private function extractHabitsByRisk(array $advancedInsights, string $riskLevel): array
    {
        $matches = [];

        foreach ($advancedInsights as $insight) {
            if (($insight['risk']['riskLevel'] ?? null) === $riskLevel) {
                $matches[] = (string) ($insight['risk']['habitName'] ?? '');
            }
        }

        return array_values(array_filter($matches, static fn (string $name): bool => $name !== ''));
    }

    /**
     * @param array<int, array<string, mixed>> $advancedInsights
     */
    private function findBestHabit(array $advancedInsights): ?string
    {
        $bestName = null;
        $bestScore = -1.0;

        foreach ($advancedInsights as $insight) {
            $progress = (float) ($insight['progress']['completionRate'] ?? 0);
            $streak = (float) ($insight['streak']['currentStreak'] ?? 0);
            $score = $progress + ($streak * 3);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestName = (string) ($insight['progress']['habitName'] ?? '');
            }
        }

        return $bestName !== '' ? $bestName : null;
    }

    /**
     * @param list<Habitude> $habitudes
     */
    private function findHabitMention(string $message, array $habitudes): ?Habitude
    {
        foreach ($habitudes as $habitude) {
            $habitName = $this->normalize($habitude->getNom());
            if ($habitName !== '' && str_contains($message, $habitName)) {
                return $habitude;
            }
        }

        return null;
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $normalized = trim(mb_strtolower($value));
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);

        return $converted !== false ? $converted : $normalized;
    }
}
