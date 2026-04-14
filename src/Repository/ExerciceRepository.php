<?php
namespace App\Repository;

use App\Entity\Exercice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercice::class);
    }

    public function findByFilters(?string $search = null, ?string $difficulte = null): array
    {
        $qb = $this->createQueryBuilder('e');
        
        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.type LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($difficulte) {
            $qb->andWhere('e.difficulte = :difficulte')
               ->setParameter('difficulte', $difficulte);
        }
        
        return $qb->orderBy('e.idEx', 'DESC')->getQuery()->getResult();
    }
}