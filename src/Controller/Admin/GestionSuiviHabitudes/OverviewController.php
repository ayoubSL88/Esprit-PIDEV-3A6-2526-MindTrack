<?php

namespace App\Controller\Admin\GestionSuiviHabitudes;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/admin/habitudes', name: 'admin_gestion_suivi_habitudes_index')]
    public function index(): Response
    {
        return $this->render('admin/gestion_suivi_habitudes/index.html.twig');
    }
}
