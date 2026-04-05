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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * @return Utilisateur[]
     */
    public function findForAdminList(?string $search, ?string $role): array
    {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.idU', 'DESC');

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

        return $qb->getQuery()->getResult();
    }
}