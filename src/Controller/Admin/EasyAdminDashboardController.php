<?php

namespace App\Controller\Admin;

use App\Entity\Exercice;
use App\Entity\Session;
use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class EasyAdminDashboardController extends AbstractDashboardController
{
    #[Route('/admin/easy', name: 'admin_easy_dashboard')]
    public function index(): Response
    {
        return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('MindTrack Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', Utilisateur::class);
        yield MenuItem::linkToCrud('Exercices', 'fa fa-book', Exercice::class);
        yield MenuItem::linkToCrud('Sessions', 'fa fa-play-circle', Session::class);
        yield MenuItem::section('Application');
        yield MenuItem::linkToRoute('Administration actuelle', 'fa fa-arrow-left', 'admin_dashboard');
    }
}
