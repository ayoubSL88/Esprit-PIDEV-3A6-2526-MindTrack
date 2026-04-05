<?php

namespace App\Controller\Front\GestionExercices;

use App\Entity\Exercice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/exercices')]
final class ExerciceController extends AbstractController
{
    #[Route('/', name: 'front_gestion_exercices_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $exercices = $entityManager
            ->getRepository(Exercice::class)
            ->findAll();

        return $this->render('front/gestion_exercices/index.html.twig', [
            'exercices' => $exercices,
        ]);
    }
}
