<?php

namespace App\Repository;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Entity\Utilisateur;
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

        if (($filters['owner'] ?? null) instanceof Utilisateur) {
            $qb
                ->andWhere('h.idU = :owner')
                ->setParameter('owner', $filters['owner']);
        }

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

    public function countAllForUser(Utilisateur $user): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.idSuivi)')
            ->innerJoin('s.idHabitude', 'h')
            ->andWhere('h.idU = :owner')
            ->setParameter('owner', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countCompletedForUser(Utilisateur $user): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.idSuivi)')
            ->innerJoin('s.idHabitude', 'h')
            ->andWhere('h.idU = :owner')
            ->andWhere('s.etat = true')
            ->setParameter('owner', $user)
            ->getQuery()
            ->getSingleScalarResult();
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

    /**
     * @return list<Suivihabitude>
     */
    public function findForHabitOnDate(Habitude $habitude, \DateTimeInterface $date): array
    {
        $start = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $end = $start->modify('+1 day');

        return $this->createQueryBuilder('s')
            ->andWhere('s.idHabitude = :habitude')
            ->andWhere('s.date >= :start')
            ->andWhere('s.date < :end')
            ->setParameter('habitude', $habitude)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
