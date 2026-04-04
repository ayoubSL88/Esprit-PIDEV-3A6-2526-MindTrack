<?php

namespace App\Repository;

use App\Entity\Jalonprogression;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Jalonprogression>
 */
class JalonprogressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Jalonprogression::class);
    }

    /**
     * @return Jalonprogression[]
     */
    public function findBySearchSortAndStatus(?string $search, ?string $sort, ?string $status): array
    {
        $qb = $this->createQueryBuilder('j')
            ->leftJoin('j.idObj', 'o')
            ->addSelect('o');

        if ($search) {
            $qb
                ->andWhere('LOWER(j.titre) LIKE :search OR LOWER(o.titre) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        if ($status) {
            $qb->andWhere($status === 'atteint' ? 'j.atteint = true' : 'j.atteint = false');
        }

        match ($sort) {
            'date_asc' => $qb->orderBy('j.dateCible', 'ASC'),
            'date_atteinte_desc' => $qb->orderBy('j.dateAtteinte', 'DESC'),
            'date_atteinte_asc' => $qb->orderBy('j.dateAtteinte', 'ASC'),
            'statut_asc' => $qb->orderBy('j.atteint', 'ASC')->addOrderBy('j.dateCible', 'ASC'),
            'statut_desc' => $qb->orderBy('j.atteint', 'DESC')->addOrderBy('j.dateCible', 'DESC'),
            default => $qb->orderBy('j.dateCible', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $maxId = $this->createQueryBuilder('j')
            ->select('MAX(j.idJalon)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxId) + 1;
    }
}
