<?php

namespace App\Controller\Admin\GestionExercices;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/exercices')]
final class ExerciceController extends AbstractController
{
    #[Route('/', name: 'admin_gestion_exercices_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les paramètres de filtrage depuis l'URL
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
        // Trier par ID décroissant (optionnel)
        $qb->orderBy('e.idEx', 'DESC');
        
        // Exécuter la requête
        $exercices = $qb->getQuery()->getResult();
        
        // Retourner la vue avec les paramètres de filtrage pour pré-remplir le formulaire
        return $this->render('admin/gestion_exercices/index.html.twig', [
            'exercices' => $exercices,
            'search' => $search,
            'difficulte' => $difficulte,
        ]);
    }

    #[Route('/new', name: 'admin_gestion_exercices_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $exercice = new Exercice();
        $form = $this->createForm(ExerciceType::class, $exercice, [
            'is_edit' => false
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        // Les dates sont automatiquement gérées par PrePersist
        $entityManager->persist($exercice);
        $entityManager->flush();
        
        $this->addFlash('success', 'Exercice créé avec succès !');
        return $this->redirectToRoute('admin_gestion_exercices_index');
        }
    
        return $this->render('admin/gestion_exercices/new.html.twig', [
        'form' => $form->createView(),
     ]);
    }

    #[Route('/{idEx}', name: 'admin_gestion_exercices_show', methods: ['GET'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('admin/gestion_exercices/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route('/{idEx}/edit', name: 'admin_gestion_exercices_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exercice $exercice, EntityManagerInterface $entityManager): Response
    {
            // Édition : is_edit = true (affiche les dates en lecture seule)
        $form = $this->createForm(ExerciceType::class, $exercice, [
            'is_edit' => true
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // La date de modification est automatiquement mise à jour par PreUpdate
            $entityManager->flush();
            
            $this->addFlash('success', 'Exercice modifié avec succès !');
            return $this->redirectToRoute('admin_gestion_exercices_index');
        }
        
        return $this->render('admin/gestion_exercices/edit.html.twig', [
            'form' => $form->createView(),
            'exercice' => $exercice,
        ]);
    }

    #[Route('/{idEx}', name: 'admin_gestion_exercices_delete', methods: ['POST'])]
    public function delete(Request $request, Exercice $exercice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exercice->getIdEx(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($exercice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_gestion_exercices_index', [], Response::HTTP_SEE_OTHER);
    }
}
