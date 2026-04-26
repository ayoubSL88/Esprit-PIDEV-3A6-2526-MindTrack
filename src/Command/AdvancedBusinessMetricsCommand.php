<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Habitude;
use App\Entity\Humeur;
use App\Entity\Objectif;
use App\Entity\Planificateurintelligent;
use App\Entity\Planaction;
use App\Entity\Rappel_habitude;
use App\Service\Habitude\BadgeSystemService;
use App\Service\Habitude\HabitProgressService;
use App\Service\Habitude\HabitRecommendationService;
use App\Service\Habitude\HabitRiskAnalyzerService;
use App\Service\Habitude\HabitStreakService;
use App\Service\Habitude\MoodHabitCorrelationService;
use App\Service\Habitude\SmartReminderService;
use App\Service\Objectif\SmartPlannerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mindtrack:advanced-metrics',
    description: 'Execute tous les metiers avances de MindTrack sur les donnees existantes.',
)]
final class AdvancedBusinessMetricsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HabitStreakService $streakService,
        private readonly HabitProgressService $progressService,
        private readonly HabitRiskAnalyzerService $riskAnalyzerService,
        private readonly HabitRecommendationService $recommendationService,
        private readonly SmartReminderService $smartReminderService,
        private readonly MoodHabitCorrelationService $moodCorrelationService,
        private readonly BadgeSystemService $badgeSystemService,
        private readonly SmartPlannerService $smartPlannerService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var list<Habitude> $habitudes */
        $habitudes = $this->entityManager->getRepository(Habitude::class)->findAll();
        /** @var list<Humeur> $humeurs */
        $humeurs = $this->entityManager->getRepository(Humeur::class)->findAll();
        /** @var list<Rappel_habitude> $rappels */
        $rappels = $this->entityManager->getRepository(Rappel_habitude::class)->findAll();
        /** @var list<Objectif> $objectifs */
        $objectifs = $this->entityManager->getRepository(Objectif::class)->findAll();
        /** @var list<Planificateurintelligent> $planners */
        $planners = $this->entityManager->getRepository(Planificateurintelligent::class)->findAll();
        /** @var list<Planaction> $actions */
        $actions = $this->entityManager->getRepository(Planaction::class)->findAll();

        $io->title('MindTrack - Metiers avances');

        if ($habitudes === []) {
            $io->warning('Aucune habitude trouvee. Ajoute des donnees puis relance la commande.');
        }

        foreach ($habitudes as $habitude) {
            $io->section(sprintf('Habitude: %s', $habitude->getNom()));

            $io->definitionList(
                ['Streak', json_encode($this->streakService->analyze($habitude), JSON_UNESCAPED_UNICODE)],
                ['Progression', json_encode($this->progressService->analyze($habitude), JSON_UNESCAPED_UNICODE)],
                ['Risque', json_encode($this->riskAnalyzerService->analyze($habitude), JSON_UNESCAPED_UNICODE)],
                ['Recommandations', json_encode($this->recommendationService->generate($habitude), JSON_UNESCAPED_UNICODE)],
                ['Correlation humeur', json_encode($this->moodCorrelationService->analyze($habitude, $humeurs), JSON_UNESCAPED_UNICODE)],
                ['Rappel intelligent', json_encode($this->smartReminderService->suggest($habitude, $this->findReminderForHabit($habitude->getIdHabitude(), $rappels)), JSON_UNESCAPED_UNICODE)],
            );
        }

        $io->section('Badges');
        $io->writeln(json_encode($this->badgeSystemService->evaluate($habitudes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $io->section('Planificateur intelligent');

        foreach ($objectifs as $objectif) {
            $planner = $this->findPlannerForObjectif($objectif->getIdObj(), $planners);
            $objectifActions = array_values(array_filter(
                $actions,
                static fn (Planaction $action): bool => $action->getIdObj()->getIdObj() === $objectif->getIdObj()
            ));

            $io->writeln(json_encode(
                $this->smartPlannerService->buildPlan($objectif, $planner, $objectifActions),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ));
        }

        $io->success('Tous les metiers avances ont ete executes.');

        return Command::SUCCESS;
    }

    /**
     * @param list<Rappel_habitude> $rappels
     */
    private function findReminderForHabit(int $habitId, array $rappels): ?Rappel_habitude
    {
        foreach ($rappels as $rappel) {
            if ($rappel->getIdHabitude()?->getIdHabitude() === $habitId) {
                return $rappel;
            }
        }

        return null;
    }

    /**
     * @param list<Planificateurintelligent> $planners
     */
    private function findPlannerForObjectif(int $objectifId, array $planners): ?Planificateurintelligent
    {
        foreach ($planners as $planner) {
            if ($planner->getIdObj()->getIdObj() === $objectifId) {
                return $planner;
            }
        }

        return null;
    }
}
