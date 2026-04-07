<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    private const ALLOWED_SORT_FIELDS = [
        'id' => 'u.idU',
        'name' => 'u.nomU',
        'email' => 'u.emailU',
        'age' => 'u.ageU',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * @return array{items: Utilisateur[], total: int}
     */
    public function findForAdminList(?string $search, ?string $role, string $sort = 'id', string $direction = 'DESC', int $page = 1, int $perPage = 10): array
    {
        $qb = $this->createQueryBuilder('u');

        $search = trim((string) $search);
        if ($search !== '') {
            $qb
                ->andWhere('LOWER(u.nomU) LIKE :q OR LOWER(u.prenomU) LIKE :q OR LOWER(u.emailU) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        if ($role === 'ADMIN' || $role === 'USER') {
            $qb
                ->andWhere('u.roleU = :role')
                ->setParameter('role', $role);
        }

        $sortField = self::ALLOWED_SORT_FIELDS[$sort] ?? self::ALLOWED_SORT_FIELDS['id'];
        $sortDirection = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $safePage = max(1, $page);
        $safePerPage = max(1, $perPage);

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(u.idU)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy($sortField, $sortDirection)
            ->addOrderBy('u.idU', 'DESC')
            ->setFirstResult(($safePage - 1) * $safePerPage)
            ->setMaxResults($safePerPage)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
