<?php

namespace App\Repository;

use App\Entity\Habitude;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Habitude>
 */
class HabitudeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Habitude::class);
    }

    /**
     * @return list<Habitude>
     */
    public function findAdminList(array $filters = []): array
    {
        $allowedSorts = [
            'nom' => 'h.nom',
            'objectif' => 'h.objectif',
            'frequence' => 'h.frequence',
            'type' => 'h.habitType',
            'target' => 'h.targetValue',
            'id' => 'h.idHabitude',
        ];

        $sort = $filters['sort'] ?? 'nom';
        $direction = strtoupper((string) ($filters['direction'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $qb = $this->createQueryBuilder('h');

        if (($filters['q'] ?? '') !== '') {
            $qb
                ->andWhere('h.nom LIKE :term OR h.objectif LIKE :term OR h.unit LIKE :term')
                ->setParameter('term', '%' . trim((string) $filters['q']) . '%');
        }

        if (($filters['frequence'] ?? '') !== '') {
            $qb->andWhere('h.frequence = :frequence')->setParameter('frequence', $filters['frequence']);
        }

        if (($filters['habitType'] ?? '') !== '') {
            $qb->andWhere('h.habitType = :habitType')->setParameter('habitType', $filters['habitType']);
        }

        $qb->orderBy($allowedSorts[$sort] ?? 'h.nom', $direction);

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $lastId = (int) $this->createQueryBuilder('h')
            ->select('COALESCE(MAX(h.idHabitude), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return $lastId + 1;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.idHabitude)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByType(string $type): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.idHabitude)')
            ->andWhere('h.habitType = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
