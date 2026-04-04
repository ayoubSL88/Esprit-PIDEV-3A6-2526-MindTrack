<?php

namespace App\Controller\Admin\GestionHumeur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/admin/humeur', name: 'admin_gestion_humeur_index')]
    public function index(): Response
    {
        return $this->render('admin/gestion_humeur/index.html.twig');
    }
}
