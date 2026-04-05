<?php

namespace App\Repository;

use App\Entity\Planaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Planaction>
 */
class PlanactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planaction::class);
    }

    /**
     * @return Planaction[]
     */
    public function findBySearchSortAndStatus(?string $search, ?string $sort, ?string $status): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.idObj', 'o')
            ->addSelect('o');

        if ($search) {
            $qb
                ->andWhere('LOWER(p.etape) LIKE :search OR LOWER(o.titre) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        if ($status) {
            match ($status) {
                'haute' => $qb->andWhere('p.priorite >= 8'),
                'moyenne' => $qb->andWhere('p.priorite BETWEEN 4 AND 7'),
                'basse' => $qb->andWhere('p.priorite <= 3'),
                default => null,
            };
        }

        match ($sort) {
            'date_asc' => $qb->orderBy('p.idPlan', 'ASC'),
            'statut_asc' => $qb
                ->addSelect('CASE WHEN p.priorite >= 8 THEN 3 WHEN p.priorite >= 4 THEN 2 ELSE 1 END AS HIDDEN statusRank')
                ->orderBy('statusRank', 'ASC')
                ->addOrderBy('p.priorite', 'ASC'),
            'statut_desc' => $qb
                ->addSelect('CASE WHEN p.priorite >= 8 THEN 3 WHEN p.priorite >= 4 THEN 2 ELSE 1 END AS HIDDEN statusRank')
                ->orderBy('statusRank', 'DESC')
                ->addOrderBy('p.priorite', 'DESC'),
            'priorite_asc' => $qb->orderBy('p.priorite', 'ASC'),
            default => $qb->orderBy('p.priorite', 'DESC')->addOrderBy('p.idPlan', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $maxId = $this->createQueryBuilder('p')
            ->select('MAX(p.idPlan)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxId) + 1;
    }
}
