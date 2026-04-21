<?php

namespace App\Service\GestionHumeur;

use App\Entity\Humeur;

final class HumeurAnalyticsService
{
    /**
     * @param Humeur[] $humeurs
     * @return array{
     *     referenceDate: ?\DateTimeImmutable,
     *     riskLevel: string,
     *     riskLabel: string,
     *     riskMessage: string,
     *     trendDirection: string,
     *     trendLabel: string,
     *     trendMessage: string,
     *     currentStreak: int,
     *     currentWeekAverage: ?float,
     *     previousWeekAverage: ?float,
     *     currentWeekLowMoodDays: int,
     *     currentWeekTrackedDays: int,
     *     cards: array<int, array{label: string, value: string, hint: string}>,
     *     alerts: array<int, array{severity: string, badge: string, title: string, message: string}>,
     *     recommendations: array<int, array{
     *         priority: string,
     *         badge: string,
     *         title: string,
     *         message: string,
     *         actions: array<int, string>
     *     }>
     * }
     */
    public function analyze(array $humeurs): array
    {
        if ($humeurs === []) {
            return $this->emptyAnalysis();
        }

        $entries = $this->normalizeEntries($humeurs);
        usort(
            $entries,
            static fn (array $left, array $right): int => $left['date']->getTimestamp() <=> $right['date']->getTimestamp()
        );

        $dailySnapshots = $this->buildDailySnapshots($entries);
        if ($dailySnapshots === []) {
            return $this->emptyAnalysis();
        }

        $referenceSnapshot = $dailySnapshots[array_key_last($dailySnapshots)];
        $referenceDate = $referenceSnapshot['date'];
        $currentWeekStart = $this->startOfWeek($referenceDate);
        $previousWeekStart = $currentWeekStart->modify('-7 days');

        $currentWeek = $this->buildWeekStats($dailySnapshots, $currentWeekStart);
        $previousWeek = $this->buildWeekStats($dailySnapshots, $previousWeekStart);
        $currentStreak = $this->computeCurrentLowMoodStreak($dailySnapshots);
        $latestScore = $referenceSnapshot['score'];
        $delta = $previousWeek['averageScore'] === null
            ? null
            : round((float) $currentWeek['averageScore'] - (float) $previousWeek['averageScore'], 1);
        $trend = $this->buildTrend($delta);
        $risk = $this->buildRisk($currentStreak, $currentWeek, $delta, $latestScore);

        return [
            'referenceDate' => $referenceDate,
            'riskLevel' => $risk['level'],
            'riskLabel' => $risk['label'],
            'riskMessage' => $risk['message'],
            'trendDirection' => $trend['direction'],
            'trendLabel' => $trend['label'],
            'trendMessage' => $trend['message'],
            'currentStreak' => $currentStreak,
            'currentWeekAverage' => $currentWeek['averageScore'],
            'previousWeekAverage' => $previousWeek['averageScore'],
            'currentWeekLowMoodDays' => $currentWeek['lowMoodDays'],
            'currentWeekTrackedDays' => $currentWeek['trackedDays'],
            'cards' => $this->buildCards($risk, $trend, $currentStreak, $currentWeek, $previousWeek, $delta),
            'alerts' => $this->buildAlerts($currentStreak, $currentWeek, $previousWeek, $delta),
            'recommendations' => $this->buildRecommendations($entries, $referenceDate),
        ];
    }

    /**
     * @param Humeur[] $humeurs
     * @return array<int, array{
     *     date: \DateTimeImmutable,
     *     category: string,
     *     intensity: int,
     *     score: int
     * }>
     */
    private function normalizeEntries(array $humeurs): array
    {
        $entries = [];

        foreach ($humeurs as $humeur) {
            if (!$humeur instanceof Humeur) {
                continue;
            }

            $date = $humeur->getDate();
            $category = $humeur->getTypeHumeur();
            $intensity = $humeur->getIntensite();

            if ($date === null || $category === null || $intensity === null) {
                continue;
            }

            $normalizedCategory = $this->normalizeMoodType($category);
            $normalizedIntensity = max(1, min(10, $intensity));

            $entries[] = [
                'date' => \DateTimeImmutable::createFromInterface($date)->setTime(0, 0),
                'category' => $normalizedCategory,
                'intensity' => $normalizedIntensity,
                'score' => $this->scoreEntry($normalizedCategory, $normalizedIntensity),
            ];
        }

        return $entries;
    }

    private function normalizeMoodType(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = strtr($normalized, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'à' => 'a',
            'â' => 'a',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ù' => 'u',
            'û' => 'u',
        ]);

        return match ($normalized) {
            'happy', 'joyful', 'heureux', 'heureuse' => 'happy',
            'sad', 'triste' => 'sad',
            'anxious', 'stress', 'stresse', 'stressed', 'anxiete', 'anxieux', 'anxieuse' => 'stressed',
            'tired', 'fatigue', 'fatigued', 'fatiguee', 'epuise', 'epuisee', 'exhausted' => 'tired',
            'neutral', 'neutural', 'neutre', 'normal' => 'neutral',
            default => 'neutral',
        };
    }

    private function scoreEntry(string $category, int $intensity): int
    {
        $adjustment = intdiv(max($intensity - 1, 0), 3);

        return match ($category) {
            'happy' => min(10, 7 + $adjustment),
            'sad' => max(1, 4 - $adjustment),
            'stressed' => max(1, 3 - $adjustment),
            'tired' => max(1, 5 - $adjustment),
            default => max(1, min(10, 5 + ($intensity >= 8 ? 1 : ($intensity <= 3 ? -1 : 0)))),
        };
    }

    /**
     * @param array<int, array{
     *     date: \DateTimeImmutable,
     *     category: string,
     *     intensity: int,
     *     score: int
     * }> $entries
     * @return array<int, array{
     *     date: \DateTimeImmutable,
     *     score: float,
     *     lowMoodDay: bool,
     *     entryCount: int
     * }>
     */
    private function buildDailySnapshots(array $entries): array
    {
        $grouped = [];

        foreach ($entries as $entry) {
            $key = $entry['date']->format('Y-m-d');

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'date' => $entry['date'],
                    'scoreTotal' => 0,
                    'entryCount' => 0,
                    'hasStrongNegativeEntry' => false,
                ];
            }

            $grouped[$key]['scoreTotal'] += $entry['score'];
            $grouped[$key]['entryCount'] += 1;
            $grouped[$key]['hasStrongNegativeEntry'] = $grouped[$key]['hasStrongNegativeEntry']
                || (in_array($entry['category'], ['sad', 'stressed', 'tired'], true) && $entry['intensity'] >= 7);
        }

        ksort($grouped);

        $snapshots = [];
        foreach ($grouped as $group) {
            $score = round($group['scoreTotal'] / $group['entryCount'], 1);

            $snapshots[] = [
                'date' => $group['date'],
                'score' => $score,
                'lowMoodDay' => $group['hasStrongNegativeEntry'] || $score <= 3.5,
                'entryCount' => $group['entryCount'],
            ];
        }

        return $snapshots;
    }

    /**
     * @param array<int, array{
     *     date: \DateTimeImmutable,
     *     score: float,
     *     lowMoodDay: bool,
     *     entryCount: int
     * }> $dailySnapshots
     * @return array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     trackedDays: int,
     *     lowMoodDays: int,
     *     averageScore: ?float
     * }
     */
    private function buildWeekStats(array $dailySnapshots, \DateTimeImmutable $weekStart): array
    {
        $weekEnd = $weekStart->modify('+6 days');
        $scoreTotal = 0.0;
        $trackedDays = 0;
        $lowMoodDays = 0;

        foreach ($dailySnapshots as $snapshot) {
            if ($snapshot['date'] < $weekStart || $snapshot['date'] > $weekEnd) {
                continue;
            }

            $scoreTotal += $snapshot['score'];
            $trackedDays += 1;
            if ($snapshot['lowMoodDay']) {
                $lowMoodDays += 1;
            }
        }

        return [
            'start' => $weekStart,
            'end' => $weekEnd,
            'trackedDays' => $trackedDays,
            'lowMoodDays' => $lowMoodDays,
            'averageScore' => $trackedDays > 0 ? round($scoreTotal / $trackedDays, 1) : null,
        ];
    }

    /**
     * @param array<int, array{
     *     date: \DateTimeImmutable,
     *     score: float,
     *     lowMoodDay: bool,
     *     entryCount: int
     * }> $dailySnapshots
     */
    private function computeCurrentLowMoodStreak(array $dailySnapshots): int
    {
        $streak = 0;
        $expectedDate = null;

        for ($index = count($dailySnapshots) - 1; $index >= 0; --$index) {
            $snapshot = $dailySnapshots[$index];

            if ($expectedDate !== null && $snapshot['date']->format('Y-m-d') !== $expectedDate->format('Y-m-d')) {
                break;
            }

            if (!$snapshot['lowMoodDay']) {
                break;
            }

            $streak += 1;
            $expectedDate = $snapshot['date']->modify('-1 day');
        }

        return $streak;
    }

    private function startOfWeek(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify('monday this week')->setTime(0, 0);
    }

    /**
     * @param array{direction: string, label: string, message: string} $trend
     * @param array{level: string, label: string, message: string} $risk
     * @param array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     trackedDays: int,
     *     lowMoodDays: int,
     *     averageScore: ?float
     * } $currentWeek
     * @param array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     trackedDays: int,
     *     lowMoodDays: int,
     *     averageScore: ?float
     * } $previousWeek
     * @return array<int, array{label: string, value: string, hint: string}>
     */
    private function buildCards(
        array $risk,
        array $trend,
        int $currentStreak,
        array $currentWeek,
        array $previousWeek,
        ?float $delta
    ): array {
        return [
            [
                'label' => 'Risk level',
                'value' => $risk['label'],
                'hint' => $risk['message'],
            ],
            [
                'label' => 'Trend',
                'value' => $trend['label'],
                'hint' => $trend['message'],
            ],
            [
                'label' => 'Difficult streak',
                'value' => $currentStreak === 0 ? 'No streak' : sprintf('%d day%s', $currentStreak, $currentStreak > 1 ? 's' : ''),
                'hint' => $currentStreak === 0
                    ? 'The latest tracked day is not part of an active difficult-mood streak.'
                    : 'Counts consecutive difficult days at the end of the tracked timeline.',
            ],
            [
                'label' => 'Current week average',
                'value' => $this->formatScore($currentWeek['averageScore']),
                'hint' => $delta === null
                    ? sprintf(
                        '%d difficult day%s across %d tracked day%s this week. Previous-week comparison will unlock once more history is available.',
                        $currentWeek['lowMoodDays'],
                        $currentWeek['lowMoodDays'] === 1 ? '' : 's',
                        $currentWeek['trackedDays'],
                        $currentWeek['trackedDays'] === 1 ? '' : 's'
                    )
                    : sprintf(
                        '%d difficult day%s across %d tracked day%s this week. Previous week: %s.',
                        $currentWeek['lowMoodDays'],
                        $currentWeek['lowMoodDays'] === 1 ? '' : 's',
                        $currentWeek['trackedDays'],
                        $currentWeek['trackedDays'] === 1 ? '' : 's',
                        $this->formatScore($previousWeek['averageScore'])
                    ),
            ],
        ];
    }

    /**
     * @param array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     trackedDays: int,
     *     lowMoodDays: int,
     *     averageScore: ?float
     * } $currentWeek
     * @param array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     trackedDays: int,
     *     lowMoodDays: int,
     *     averageScore: ?float
     * } $previousWeek
     * @return array<int, array{severity: string, badge: string, title: string, message: string}>
     */
    private function buildAlerts(
        int $currentStreak,
        array $currentWeek,
        array $previousWeek,
        ?float $delta
    ): array {
        $alerts = [];

        if ($currentStreak >= 3) {
            $alerts[] = [
                'severity' => 'high',
                'badge' => 'High alert',
                'title' => 'Low mood streak detected',
                'message' => sprintf(
                    'The last %d tracked days were difficult. This usually deserves a quicker follow-up.',
                    $currentStreak
                ),
            ];
        } elseif ($currentStreak === 2) {
            $alerts[] = [
                'severity' => 'medium',
                'badge' => 'Watch',
                'title' => 'Two difficult days in a row',
                'message' => 'The recent pattern shows back-to-back difficult days and may be the start of a larger drop.',
            ];
        }

        if ($delta !== null && $delta <= -1.0) {
            $alerts[] = [
                'severity' => $delta <= -2.0 ? 'high' : 'medium',
                'badge' => $delta <= -2.0 ? 'High alert' : 'Watch',
                'title' => 'Weekly average is dropping',
                'message' => sprintf(
                    'The current week average is %s compared with %s during the previous week.',
                    $this->formatScore($currentWeek['averageScore']),
                    $this->formatScore($previousWeek['averageScore'])
                ),
            ];
        } elseif ($delta !== null && $delta >= 1.0) {
            $alerts[] = [
                'severity' => 'low',
                'badge' => 'Positive signal',
                'title' => 'Weekly average improved',
                'message' => sprintf(
                    'The current week average climbed to %s from %s last week.',
                    $this->formatScore($currentWeek['averageScore']),
                    $this->formatScore($previousWeek['averageScore'])
                ),
            ];
        }

        if ($currentWeek['lowMoodDays'] >= 4) {
            $alerts[] = [
                'severity' => 'high',
                'badge' => 'High alert',
                'title' => 'Several difficult days this week',
                'message' => sprintf(
                    '%d of the tracked days in the current week were flagged as difficult.',
                    $currentWeek['lowMoodDays']
                ),
            ];
        } elseif ($currentWeek['lowMoodDays'] >= 2) {
            $alerts[] = [
                'severity' => 'medium',
                'badge' => 'Watch',
                'title' => 'Repeated difficult days this week',
                'message' => sprintf(
                    '%d difficult days were detected in the current week, even if they were not fully consecutive.',
                    $currentWeek['lowMoodDays']
                ),
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'severity' => 'low',
                'badge' => 'Stable',
                'title' => 'No active warning signal',
                'message' => $delta === null
                    ? 'The recent pattern looks stable so far. Add more entries to unlock week-over-week comparison.'
                    : 'The recent pattern looks stable overall, with no strong warning signal in the current window.',
            ];
        }

        return $alerts;
    }

    /**
     * @param array{
     *     start: \DateTimeImmutable,
     *     end: \DateTimeImmutable,
     *     trackedDays: int,
     *     lowMoodDays: int,
     *     averageScore: ?float
     * } $currentWeek
     * @return array{level: string, label: string, message: string}
     */
    private function buildRisk(int $currentStreak, array $currentWeek, ?float $delta, float $latestScore): array
    {
        $points = 0;

        if ($currentStreak >= 3) {
            $points += 4;
        } elseif ($currentStreak === 2) {
            $points += 2;
        }

        if ($currentWeek['lowMoodDays'] >= 4) {
            $points += 3;
        } elseif ($currentWeek['lowMoodDays'] >= 2) {
            $points += 1;
        }

        if ($delta !== null) {
            if ($delta <= -2.0) {
                $points += 3;
            } elseif ($delta <= -1.0) {
                $points += 2;
            } elseif ($delta <= -0.5) {
                $points += 1;
            }
        }

        if ($latestScore <= 2.0) {
            $points += 2;
        } elseif ($latestScore <= 3.5) {
            $points += 1;
        }

        if ($points >= 6) {
            return [
                'level' => 'high',
                'label' => 'High',
                'message' => 'Recent data shows a sustained deterioration. This profile should be reviewed first.',
            ];
        }

        if ($points >= 3) {
            return [
                'level' => 'medium',
                'label' => 'Medium',
                'message' => 'Some warning signs are visible. Keep a closer eye on the next entries.',
            ];
        }

        return [
            'level' => 'low',
            'label' => 'Low',
            'message' => 'The recent pattern is broadly stable. Continue tracking to confirm the trend.',
        ];
    }

    /**
     * @return array{direction: string, label: string, message: string}
     */
    private function buildTrend(?float $delta): array
    {
        if ($delta === null) {
            return [
                'direction' => 'unknown',
                'label' => 'Not enough history',
                'message' => 'At least one tracked day in the previous week is needed for a reliable comparison.',
            ];
        }

        if ($delta <= -0.5) {
            return [
                'direction' => 'declining',
                'label' => 'Declining',
                'message' => sprintf('This week dropped by %.1f point%s compared with the previous week.', abs($delta), abs($delta) === 1.0 ? '' : 's'),
            ];
        }

        if ($delta >= 0.5) {
            return [
                'direction' => 'improving',
                'label' => 'Improving',
                'message' => sprintf('This week improved by %.1f point%s compared with the previous week.', $delta, $delta === 1.0 ? '' : 's'),
            ];
        }

        return [
            'direction' => 'stable',
            'label' => 'Stable',
            'message' => 'This week is broadly in line with the previous week.',
        ];
    }

    /**
     * @param array<int, array{
     *     date: \DateTimeImmutable,
     *     category: string,
     *     intensity: int,
     *     score: int
     * }> $entries
     * @return array<int, array{
     *     priority: string,
     *     badge: string,
     *     title: string,
     *     message: string,
     *     actions: array<int, string>
     * }>
     */
    private function buildRecommendations(array $entries, \DateTimeImmutable $referenceDate): array
    {
        $recentEntries = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry['date'] >= $referenceDate->modify('-13 days')
        ));

        if ($recentEntries === []) {
            return [];
        }

        $latestEntry = $recentEntries[array_key_last($recentEntries)];
        $counts = [
            'sad' => 0,
            'stressed' => 0,
            'tired' => 0,
            'happy' => 0,
            'neutral' => 0,
        ];
        $highIntensityCounts = $counts;
        $daysByCategory = [
            'sad' => [],
            'stressed' => [],
            'tired' => [],
            'happy' => [],
            'neutral' => [],
        ];

        foreach ($recentEntries as $entry) {
            $category = $entry['category'];
            if (!array_key_exists($category, $counts)) {
                continue;
            }

            $counts[$category] += 1;
            if ($entry['intensity'] >= 7) {
                $highIntensityCounts[$category] += 1;
            }

            $daysByCategory[$category][$entry['date']->format('Y-m-d')] = true;
        }

        $recommendations = [];
        $stressedDays = count($daysByCategory['stressed']);
        $sadDays = count($daysByCategory['sad']);
        $tiredDays = count($daysByCategory['tired']);

        if ($latestEntry['category'] === 'stressed' || $counts['stressed'] >= 2 || $highIntensityCounts['stressed'] >= 1) {
            $variant = $this->rotateRecommendation([
                [
                    'title' => 'Try a short breathing reset',
                    'message' => 'Your recent mood logs point to active pressure right now.',
                    'actions' => [
                        'Do one minute of slow breathing.',
                        'Take a five-minute pause away from screens.',
                        'Play one calming song all the way through.',
                    ],
                ],
                [
                    'title' => 'Create a mini recovery break',
                    'message' => 'A short pause can stop tension from stacking up.',
                    'actions' => [
                        'Step away from your task for five minutes.',
                        'Relax your shoulders and unclench your jaw.',
                        'Come back with one smaller next step.',
                    ],
                ],
                [
                    'title' => 'Use calming audio on purpose',
                    'message' => 'Sound can help your body settle faster when stress is present.',
                    'actions' => [
                        'Start a relaxing playlist or ambient track.',
                        'Lower stimulation for a few minutes.',
                        'Pair the music with slow breathing.',
                    ],
                ],
            ], $counts['stressed']);

            $recommendations[] = [
                'priority' => ($latestEntry['category'] === 'stressed' && $latestEntry['intensity'] >= 7) || $highIntensityCounts['stressed'] >= 2 ? 'high' : 'medium',
                'badge' => 'Quick reset',
                'title' => $variant['title'],
                'message' => sprintf(
                    '%s Stressed moods appeared %d time%s across %d day%s in the last two weeks.',
                    $variant['message'],
                    $counts['stressed'],
                    $this->pluralSuffix($counts['stressed']),
                    $stressedDays,
                    $this->pluralSuffix($stressedDays)
                ),
                'actions' => $variant['actions'],
            ];
        }

        if ($counts['sad'] >= 3 || $sadDays >= 2) {
            $variant = $this->rotateRecommendation([
                [
                    'title' => 'Plan one small social moment',
                    'message' => 'Repeated sadness often softens when you add safe connection instead of isolation.',
                    'actions' => [
                        'Send one message to someone you trust.',
                        'Plan a short walk, coffee, or call with someone supportive.',
                        'Choose one light social activity for today.',
                    ],
                ],
                [
                    'title' => 'Reach out to a support person',
                    'message' => 'A quick check-in can create support before the feeling gets heavier.',
                    'actions' => [
                        'Text or call one trusted person.',
                        'Say clearly that today feels heavy.',
                        'Ask for company, listening, or practical help.',
                    ],
                ],
                [
                    'title' => 'Choose connection over isolation today',
                    'message' => 'A gentle shared activity can interrupt a low pattern.',
                    'actions' => [
                        'Spend 15 minutes with a friend, classmate, or family member.',
                        'Work or study near other people if possible.',
                        'If the sadness keeps building, consider professional support.',
                    ],
                ],
            ], $counts['sad']);

            $recommendations[] = [
                'priority' => $counts['sad'] >= 4 || $highIntensityCounts['sad'] >= 2 ? 'high' : 'medium',
                'badge' => 'Connection',
                'title' => $variant['title'],
                'message' => sprintf(
                    '%s Sad moods were logged %d time%s across %d day%s in the last two weeks.',
                    $variant['message'],
                    $counts['sad'],
                    $this->pluralSuffix($counts['sad']),
                    $sadDays,
                    $this->pluralSuffix($sadDays)
                ),
                'actions' => $variant['actions'],
            ];
        }

        if ($counts['tired'] >= 2 || $tiredDays >= 2) {
            $variant = $this->rotateRecommendation([
                [
                    'title' => 'Protect recovery time today',
                    'message' => 'Frequent tired entries suggest your system may need more rest than usual.',
                    'actions' => [
                        'Block a short rest window in your day.',
                        'Drop or delay one non-essential task.',
                        'Hydrate and take a few slow stretches.',
                    ],
                ],
                [
                    'title' => 'Track your sleep for the next few nights',
                    'message' => 'A light sleep check can reveal patterns behind recurring fatigue.',
                    'actions' => [
                        'Note your bedtime and wake time tonight.',
                        'Keep the evening routine simpler than usual.',
                        'Notice late caffeine or screen time.',
                    ],
                ],
                [
                    'title' => 'Lower the load early',
                    'message' => 'When tiredness repeats, removing one demand can create real relief.',
                    'actions' => [
                        'Move one demanding task to a better time.',
                        'Pick the most important task and keep the rest lighter.',
                        'Add one restorative habit before bed.',
                    ],
                ],
            ], $counts['tired']);

            $recommendations[] = [
                'priority' => $counts['tired'] >= 4 || $highIntensityCounts['tired'] >= 2 ? 'high' : 'medium',
                'badge' => 'Recovery',
                'title' => $variant['title'],
                'message' => sprintf(
                    '%s Tired moods appeared %d time%s across %d day%s in the last two weeks.',
                    $variant['message'],
                    $counts['tired'],
                    $this->pluralSuffix($counts['tired']),
                    $tiredDays,
                    $this->pluralSuffix($tiredDays)
                ),
                'actions' => $variant['actions'],
            ];
        }

        if ($recommendations === []) {
            if ($latestEntry['category'] === 'happy' || $counts['happy'] >= 2) {
                $variant = $this->rotateRecommendation([
                    [
                        'title' => 'Keep the routine that is helping',
                        'message' => 'Recent logs suggest something is working for you.',
                        'actions' => [
                            'Notice what helped this better day happen.',
                            'Repeat one helpful habit tomorrow.',
                            'Keep logging moods once a day.',
                        ],
                    ],
                    [
                        'title' => 'Capture the positive pattern',
                        'message' => 'Saving what works now makes it easier to return to it later.',
                        'actions' => [
                            'Write down one thing that lifted your mood.',
                            'Protect time for that habit again this week.',
                            'Use your journal to note the context.',
                        ],
                    ],
                ], $counts['happy']);

                $recommendations[] = [
                    'priority' => 'low',
                    'badge' => 'Keep it going',
                    'title' => $variant['title'],
                    'message' => $variant['message'],
                    'actions' => $variant['actions'],
                ];
            } else {
                $recommendations[] = [
                    'priority' => 'low',
                    'badge' => 'Check-in',
                    'title' => 'Keep tracking how your mood shifts',
                    'message' => 'There is not a strong pattern yet, so the best next step is a gentle daily check-in. Suggestions will become more tailored as more entries are logged.',
                    'actions' => [
                        'Add one mood entry each day when you can.',
                        'Notice what tends to help on neutral days.',
                        'Use the journal to capture context around changes.',
                    ],
                ];
            }
        }

        usort($recommendations, function (array $left, array $right): int {
            $weights = [
                'high' => 3,
                'medium' => 2,
                'low' => 1,
            ];

            return ($weights[$right['priority']] ?? 0) <=> ($weights[$left['priority']] ?? 0);
        });

        return array_slice($recommendations, 0, 3);
    }

    /**
     * @param array<int, array{
     *     title: string,
     *     message: string,
     *     actions: array<int, string>
     * }> $variants
     * @return array{
     *     title: string,
     *     message: string,
     *     actions: array<int, string>
     * }
     */
    private function rotateRecommendation(array $variants, int $occurrenceCount): array
    {
        return $variants[(max($occurrenceCount, 1) - 1) % count($variants)];
    }

    private function pluralSuffix(int $count): string
    {
        return $count === 1 ? '' : 's';
    }

    private function formatScore(?float $score): string
    {
        return $score === null ? 'n/a' : number_format($score, 1).'/10';
    }

    /**
     * @return array{
     *     referenceDate: null,
     *     riskLevel: string,
     *     riskLabel: string,
     *     riskMessage: string,
     *     trendDirection: string,
     *     trendLabel: string,
     *     trendMessage: string,
     *     currentStreak: int,
     *     currentWeekAverage: ?float,
     *     previousWeekAverage: ?float,
     *     currentWeekLowMoodDays: int,
     *     currentWeekTrackedDays: int,
     *     cards: array<int, array{label: string, value: string, hint: string}>,
     *     alerts: array<int, array{severity: string, badge: string, title: string, message: string}>,
     *     recommendations: array<int, array{
     *         priority: string,
     *         badge: string,
     *         title: string,
     *         message: string,
     *         actions: array<int, string>
     *     }>
     * }
     */
    private function emptyAnalysis(): array
    {
        return [
            'referenceDate' => null,
            'riskLevel' => 'low',
            'riskLabel' => 'Low',
            'riskMessage' => 'No mood data is available yet.',
            'trendDirection' => 'unknown',
            'trendLabel' => 'Not enough history',
            'trendMessage' => 'Start tracking moods to unlock advanced analysis.',
            'currentStreak' => 0,
            'currentWeekAverage' => null,
            'previousWeekAverage' => null,
            'currentWeekLowMoodDays' => 0,
            'currentWeekTrackedDays' => 0,
            'cards' => [],
            'alerts' => [],
            'recommendations' => [],
        ];
    }
}
