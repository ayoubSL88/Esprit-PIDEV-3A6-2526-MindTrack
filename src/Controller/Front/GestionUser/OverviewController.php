<?php

namespace App\Controller\Front\GestionUser;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OverviewController extends AbstractController
{
    #[Route('/app/users', name: 'front_gestion_user_index')]
    public function index(): Response
    {
        return $this->render('front/gestion_user/index.html.twig');
    }
}
