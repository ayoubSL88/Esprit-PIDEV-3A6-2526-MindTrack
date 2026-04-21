<?php
namespace App\Repository;

use App\Entity\Session;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    public function findSessionsByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTermineesByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.terminee = true')
            ->setParameter('user', $user)
            ->orderBy('s.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllSessionsForAdmin(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e')
            ->addSelect('u', 'e')
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}