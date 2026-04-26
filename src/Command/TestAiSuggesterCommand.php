<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

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
        $helper = $this->getHelper('question');
        $question = new Question('Entrez votre humeur (1-10) : ', 5);
        $mood = $helper->ask($input, $output, $question);
        
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