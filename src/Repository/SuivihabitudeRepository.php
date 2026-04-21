<?php

namespace App\Repository;

use App\Entity\Suivihabitude;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Suivihabitude>
 */
class SuivihabitudeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Suivihabitude::class);
    }

    /**
     * @return list<Suivihabitude>
     */
    public function findAdminList(array $filters = []): array
    {
        $allowedSorts = [
            'date' => 's.date',
            'etat' => 's.etat',
            'valeur' => 's.valeur',
            'habitude' => 'h.nom',
            'id' => 's.idSuivi',
        ];

        $sort = $filters['sort'] ?? 'date';
        $direction = strtoupper((string) ($filters['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.idHabitude', 'h')
            ->addSelect('h');

        if (($filters['habitude'] ?? '') !== '') {
            $qb->andWhere('IDENTITY(s.idHabitude) = :habitude')->setParameter('habitude', (int) $filters['habitude']);
        }

        if (($filters['etat'] ?? '') !== '') {
            $qb->andWhere('s.etat = :etat')->setParameter('etat', $filters['etat'] === '1');
        }

        if (($filters['q'] ?? '') !== '') {
            $qb->andWhere('h.nom LIKE :term')->setParameter('term', '%' . trim((string) $filters['q']) . '%');
        }

        $qb->orderBy($allowedSorts[$sort] ?? 's.date', $direction);

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $lastId = (int) $this->createQueryBuilder('s')
            ->select('COALESCE(MAX(s.idSuivi), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return $lastId + 1;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.idSuivi)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompleted(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.idSuivi)')
            ->andWhere('s.etat = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
