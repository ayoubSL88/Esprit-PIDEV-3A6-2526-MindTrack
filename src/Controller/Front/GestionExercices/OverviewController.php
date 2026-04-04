<?php

namespace App\Controller\Front\GestionExercices;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/app/exercices', name: 'front_gestion_exercices_index')]
    public function index(): Response
    {
        return $this->render('front/gestion_exercices/index.html.twig');
    }
}
