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
        }
        
        return $progression;
    }

    public function getStatsForUser(Utilisateur $user): array
    {
        $qb = $this->createQueryBuilder('p');
        
        $total = $qb->select('COUNT(p.idProgression)')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
            
        return [
            'total_exercices' => $total,
            'derniere_activite' => $this->findOneBy(['user' => $user], ['derniereActivite' => 'DESC'])
        ];
    }
}