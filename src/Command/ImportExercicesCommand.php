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
    name: 'app:import-exercices',
    description: 'Importe les exercices depuis un fichier JSON'
)]
class ImportExercicesCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $file = __DIR__ . '/../../data/exercices.json';
        if (!file_exists($file)) {
            $io->error('Le fichier data/exercices.json n\'existe pas');
            return Command::FAILURE;
        }
        
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        
        $count = 0;
        foreach ($data as $item) {
            // Vérifier si l'exercice existe déjà
            $existing = $this->em->getRepository(Exercice::class)->findOneBy(['nom' => $item['nom']]);
            
            if (!$existing) {
                $exercice = new Exercice();
                $exercice->setNom($item['nom']);
                $exercice->setType($item['type']);
                $exercice->setDuree($item['duree']);
                $exercice->setDifficulte($item['difficulte']);
                $exercice->setDescription($item['description']);
                $exercice->setDemarche($item['demarche']);
                
                $this->em->persist($exercice);
                $count++;
            }
        }
        
        $this->em->flush();
        $io->success($count . ' nouveaux exercices importés');
        return Command::SUCCESS;
    }
}