<?php

namespace App\Repository;

use App\Entity\Objectif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Objectif>
 */
class ObjectifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objectif::class);
    }

    /**
     * @return Objectif[]
     */
    public function findBySearchSortAndStatus(?string $search, ?string $sort, ?string $status): array
    {
        $qb = $this->createQueryBuilder('o');

        if ($search) {
            $qb
                ->andWhere('LOWER(o.titre) LIKE :search OR LOWER(o.descriprion) LIKE :search OR LOWER(o.statut) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        if ($status) {
            $qb
                ->andWhere('LOWER(o.statut) = :status')
                ->setParameter('status', mb_strtolower(trim($status)));
        }

        match ($sort) {
            'date_asc' => $qb->orderBy('o.dateDebut', 'ASC'),
            'fin_asc' => $qb->orderBy('o.dateFin', 'ASC'),
            'fin_desc' => $qb->orderBy('o.dateFin', 'DESC'),
            'statut_asc' => $qb->orderBy('o.statut', 'ASC')->addOrderBy('o.dateDebut', 'ASC'),
            'statut_desc' => $qb->orderBy('o.statut', 'DESC')->addOrderBy('o.dateDebut', 'DESC'),
            default => $qb->orderBy('o.dateDebut', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $maxId = $this->createQueryBuilder('o')
            ->select('MAX(o.idObj)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxId) + 1;
    }
}
