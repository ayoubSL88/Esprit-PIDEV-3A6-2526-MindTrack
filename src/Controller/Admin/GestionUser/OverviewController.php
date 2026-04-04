<?php

namespace App\Controller\Admin\GestionUser;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_gestion_user_index')]
    public function index(): Response
    {
        return $this->render('admin/gestion_user/index.html.twig');
    }
}
