<?php

namespace App\Command;

use App\Entity\Exercice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export-exercices',
    description: 'Exporte tous les exercices vers un fichier JSON'
)]
class ExportExercicesCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $exercices = $this->em->getRepository(Exercice::class)->findAll();
        
        $data = [];
        foreach ($exercices as $exercice) {
            $data[] = [
                'nom' => $exercice->getNom(),
                'type' => $exercice->getType(),
                'duree' => $exercice->getDuree(),
                'difficulte' => $exercice->getDifficulte(),
                'description' => $exercice->getDescription(),
                'demarche' => $exercice->getDemarche(),
            ];
        }
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents(__DIR__ . '/../../data/exercices.json', $json);
        
        $io->success(count($data) . ' exercices exportés dans data/exercices.json');
        return Command::SUCCESS;
    }
}