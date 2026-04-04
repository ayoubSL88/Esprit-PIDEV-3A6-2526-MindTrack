<?php

namespace App\Controller\Admin\GestionObjectifsPersonnelles;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/admin/objectifs', name: 'admin_gestion_objectifs_personnelles_index')]
    public function index(): Response
    {
        return $this->render('admin/gestion_objectifs_personnelles/index.html.twig');
    }
}
