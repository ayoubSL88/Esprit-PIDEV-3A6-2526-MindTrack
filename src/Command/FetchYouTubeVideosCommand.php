<?php

namespace App\Command;

use App\Service\YouTubeRecommendationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:youtube:fetch',
    description: 'Récupère des vidéos YouTube et met à jour le système de recommandation',
)]
class FetchYouTubeVideosCommand extends Command
{
    public function __construct(private YouTubeRecommendationService $youtubeService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::OPTIONAL, 'Terme de recherche', 'développement personnel')
            ->addArgument('max', InputArgument::OPTIONAL, 'Nombre maximum de vidéos', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = $input->getArgument('query');
        $max = (int)$input->getArgument('max');

        $io->title('Récupération des vidéos YouTube');
        $io->text(sprintf('Recherche : %s', $query));
        $io->text(sprintf('Maximum : %d vidéos', $max));

        try {
            $result = $this->youtubeService->fetchVideos($query, $max);
            
            if (isset($result['status']) && $result['status'] === 'success') {
                $io->success(sprintf('%d vidéos récupérées avec succès!', $result['count'] ?? 0));
                return Command::SUCCESS;
            } else {
                $io->error('Erreur lors de la récupération des vidéos');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}