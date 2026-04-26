<?php
namespace App\Repository;

use App\Entity\Progression;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProgressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progression::class);
    }

    public function findOrCreateForUser(Utilisateur $user): Progression
    {
        $progression = $this->findOneBy(['user' => $user]);
        
        if (!$progression) {
            $progression = new Progression();
            $progression->setUser($user);
            $progression->setTotalSessions(0);
            $progression->setSessionsTerminees(0);
            $progression->setTempsTotal(0);
            $progression->setMoyenneScore(0);
            $progression->setDerniereActivite(new \DateTime());
        }
        
        return $progression;
    }

    public function getStatsForUser(Utilisateur $user): array
    {
        $total = $this->createQueryBuilder('p')
            ->select('COUNT(p.idProgression)')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
            
        $derniere = $this->findOneBy(
            ['user' => $user], 
            ['derniereActivite' => 'DESC']
        );
        
        return [
            'total_exercices' => $total,
            'derniere_activite' => $derniere?->getDerniereActivite()
        ];
    }
}