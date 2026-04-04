<?php

namespace App\Controller\Front\GestionObjectifsPersonnelles;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/app/objectifs', name: 'front_gestion_objectifs_personnelles_index')]
    public function index(): Response
    {
        return $this->render('front/gestion_objectifs_personnelles/index.html.twig');
    }
}
