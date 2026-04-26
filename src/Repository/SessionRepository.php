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

    public function findSessionsForAdminWithFiltersQuery(string $search = '', string $statut = '', string $dateDebut = ''): \Doctrine\ORM\QueryBuilder {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e')
            ->addSelect('u', 'e');
        
        // Filtre par recherche (utilisateur ou exercice)
        if ($search !== '') {
            $qb->andWhere('u.emailU LIKE :search OR u.nomU LIKE :search OR u.prenomU LIKE :search OR e.nom LIKE :search')
            ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtre par statut
        if ($statut === 'terminee') {
            $qb->andWhere('s.terminee = true');
        } elseif ($statut === 'encours') {
            $qb->andWhere('s.terminee = false');
        }
        
        // Filtre par date de début
        if ($dateDebut !== '') {
            $date = new \DateTime($dateDebut);
            $qb->andWhere('s.dateDebut >= :dateDebut')
                ->setParameter('dateDebut', $date);
        }
        
        // Tri
        $qb->orderBy('s.dateDebut', 'DESC');
        
        return $qb;
    }

    /**
     * Retourne les résultats filtrés (sans pagination)
     */
    public function findSessionsForAdminWithFilters(string $search = '', string $statut = '', string $dateDebut = ''): array {
        return $this->findSessionsForAdminWithFiltersQuery($search, $statut, $dateDebut)
            ->getQuery()
            ->getResult();
    }
}