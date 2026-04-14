<?php

namespace App\Controller\Front\GestionExercices;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/exercices')]
final class ExerciceController extends AbstractController
{
    #[Route('/', name: 'front_gestion_exercices_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les paramètres de filtrage
        $search = $request->query->get('search', '');
        $difficulte = $request->query->get('difficulte', '');
        
        // Créer le QueryBuilder
        $qb = $entityManager->getRepository(Exercice::class)->createQueryBuilder('e');
        
        // Filtre par recherche (nom ou type)
        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.type LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }
        
        // Filtre par difficulté
        if ($difficulte) {
            $qb->andWhere('e.difficulte = :difficulte')
               ->setParameter('difficulte', $difficulte);
        }
        
        // Trier par nom
        $qb->orderBy('e.nom', 'ASC');
        
        // Exécuter la requête
        $exercices = $qb->getQuery()->getResult();
        
        return $this->render('front/gestion_exercices/index.html.twig', [
            'exercices' => $exercices,
            'search' => $search,
            'difficulte' => $difficulte,
        ]);
    }

    #[Route('/{idEx}', name: 'front_gestion_exercices_show', methods: ['GET'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('front/gestion_exercices/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }
}