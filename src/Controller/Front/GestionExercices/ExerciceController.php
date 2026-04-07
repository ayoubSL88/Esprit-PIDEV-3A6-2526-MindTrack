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
        $search = $request->query->get('search', '');
        $difficulte = $request->query->get('difficulte', '');

        $qb = $entityManager->getRepository(Exercice::class)->createQueryBuilder('e');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.type LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($difficulte) {
            $qb->andWhere('e.difficulte = :difficulte')
                ->setParameter('difficulte', $difficulte);
        }

        $qb->orderBy('e.idEx', 'DESC');
        $exercices = $qb->getQuery()->getResult();

        return $this->render('front/gestion_exercices/index.html.twig', [
            'exercices' => $exercices,
            'search' => $search,
            'difficulte' => $difficulte,
        ]);
    }

    #[Route('/new', name: 'front_gestion_exercices_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $exercice = new Exercice();
        $form = $this->createForm(ExerciceType::class, $exercice, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($exercice);
            $entityManager->flush();

            $this->addFlash('success', 'Exercice cree avec succes.');
            return $this->redirectToRoute('front_gestion_exercices_index');
        }

        return $this->render('front/gestion_exercices/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{idEx}', name: 'front_gestion_exercices_show', methods: ['GET'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('front/gestion_exercices/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route('/{idEx}/edit', name: 'front_gestion_exercices_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Exercice $exercice, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ExerciceType::class, $exercice, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Exercice modifie avec succes.');
            return $this->redirectToRoute('front_gestion_exercices_index');
        }

        return $this->render('front/gestion_exercices/edit.html.twig', [
            'form' => $form->createView(),
            'exercice' => $exercice,
        ]);
    }

    #[Route('/{idEx}', name: 'front_gestion_exercices_delete', methods: ['POST'])]
    public function delete(Request $request, Exercice $exercice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exercice->getIdEx(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($exercice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('front_gestion_exercices_index', [], Response::HTTP_SEE_OTHER);
    }
}
