<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// ✅ CORRECTION : importer le bon namespace
use App\Service\AIExerciceSuggester;

#[AsCommand(
    name: 'test:ai-suggester',
    description: 'Teste le service AIExerciceSuggester',
)]
class TestAiSuggesterCommand extends Command
{
    public function __construct(private AIExerciceSuggester $aiSuggester)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Teste le service AIExerciceSuggester');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mood = $io->ask('Entrez votre humeur (1-10)', 5);
        
        $output->writeln("\n🧠 Appel à l'IA en cours...\n");
        
        $exercice = $this->aiSuggester->suggestByMood((int)$mood);
        
        if ($exercice) {
            $output->writeln("✅ <info>Exercice suggéré : " . $exercice->getNom() . "</info>");
            $output->writeln("   📌 Type : " . $exercice->getType());
            $output->writeln("   ⭐ Difficulté : " . $exercice->getDifficulte());
            $output->writeln("   ⏱️ Durée : " . $exercice->getDuree() . " minutes");
        } else {
            $output->writeln("❌ <error>Aucun exercice trouvé</error>");
        }
        
        return Command::SUCCESS;
    }
}