<?php

namespace App\Controller\Front\GestionHumeur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/app/humeur', name: 'front_gestion_humeur_index')]
    public function index(): Response
    {
        return $this->render('front/gestion_humeur/index.html.twig');
    }
}
