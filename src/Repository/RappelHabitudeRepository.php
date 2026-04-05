<?php

namespace App\Repository;

use App\Entity\Rappel_habitude;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rappel_habitude>
 */
class RappelHabitudeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rappel_habitude::class);
    }

    /**
     * @return list<Rappel_habitude>
     */
    public function findAdminList(array $filters = []): array
    {
        $allowedSorts = [
            'heure' => 'r.heureRappel',
            'actif' => 'r.actif',
            'created' => 'r.createdAt',
            'habitude' => 'h.nom',
            'id' => 'r.idRappel',
        ];

        $sort = $filters['sort'] ?? 'created';
        $direction = strtoupper((string) ($filters['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.idHabitude', 'h')
            ->addSelect('h');

        if (($filters['habitude'] ?? '') !== '') {
            $qb->andWhere('IDENTITY(r.idHabitude) = :habitude')->setParameter('habitude', (int) $filters['habitude']);
        }

        if (($filters['actif'] ?? '') !== '') {
            $qb->andWhere('r.actif = :actif')->setParameter('actif', $filters['actif'] === '1');
        }

        if (($filters['q'] ?? '') !== '') {
            $qb
                ->andWhere('h.nom LIKE :term OR r.message LIKE :term OR r.jours LIKE :term')
                ->setParameter('term', '%' . trim((string) $filters['q']) . '%');
        }

        $qb->orderBy($allowedSorts[$sort] ?? 'r.createdAt', $direction);

        return $qb->getQuery()->getResult();
    }

    public function nextId(): int
    {
        $lastId = (int) $this->createQueryBuilder('r')
            ->select('COALESCE(MAX(r.idRappel), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return $lastId + 1;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.idRappel)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.idRappel)')
            ->andWhere('r.actif = true')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
