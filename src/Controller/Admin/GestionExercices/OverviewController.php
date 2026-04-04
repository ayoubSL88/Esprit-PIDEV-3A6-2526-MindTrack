<?php

namespace App\Controller\Admin\GestionExercices;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/admin/exercices', name: 'admin_gestion_exercices_index')]
    public function index(): Response
    {
        return $this->render('admin/gestion_exercices/index.html.twig');
    }
}
