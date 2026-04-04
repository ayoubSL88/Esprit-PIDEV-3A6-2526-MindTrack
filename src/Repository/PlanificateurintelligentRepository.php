<?php

namespace App\Repository;

use App\Entity\Planificateurintelligent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Planificateurintelligent>
 */
class PlanificateurintelligentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Planificateurintelligent::class);
    }

    /**
     * @return Planificateurintelligent[]
     */
    public function findBySearchSortAndStatus(?string $search, ?string $sort, ?string $status): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.idObj', 'o')
            ->addSelect('o');

        if ($search) {
            $qb
                ->andWhere('LOWER(p.modeOrganisation) LIKE :search OR LOWER(o.titre) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower(trim($search)) . '%');
        }

        if ($status) {
            $qb
                ->andWhere('LOWER(p.modeOrganisation) = :status')
                ->setParameter('status', mb_strtolower(trim($status)));
        }

        match ($sort) {
            'date_asc' => $qb->orderBy('p.derniereGeneration', 'ASC'),
            'statut_asc' => $qb->orderBy('p.modeOrganisation', 'ASC')->addOrderBy('p.derniereGeneration', 'DESC'),
            'statut_desc' => $qb->orderBy('p.modeOrganisation', 'DESC')->addOrderBy('p.derniereGeneration', 'DESC'),
            'capacite_asc' => $qb->orderBy('p.capaciteQuotidienne', 'ASC'),
            'capacite_desc' => $qb->orderBy('p.capaciteQuotidienne', 'DESC'),
            default => $qb->orderBy('p.derniereGeneration', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $maxId = $this->createQueryBuilder('p')
            ->select('MAX(p.idPlanificateur)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $maxId) + 1;
    }
}
