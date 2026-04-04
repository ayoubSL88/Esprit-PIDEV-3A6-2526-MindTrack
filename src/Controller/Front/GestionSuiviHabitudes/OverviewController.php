<?php

namespace App\Controller\Front\GestionSuiviHabitudes;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/app/habitudes', name: 'front_gestion_suivi_habitudes_index')]
    public function index(): Response
    {
        return $this->render('front/gestion_suivi_habitudes/index.html.twig');
    }
}
