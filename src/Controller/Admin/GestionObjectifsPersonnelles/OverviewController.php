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
        return $this->render('admin/gestion_objectifs_personnelles/index.html.twig', [
            'modules' => [
                [
                    'title' => 'Objectifs',
                    'description' => 'Consulter les objectifs et supprimer ceux qui ne sont plus utiles.',
                    'route' => 'admin_objectif_index',
                ],
                [
                    'title' => 'Plans d’action',
                    'description' => 'Voir toutes les étapes liées aux objectifs et les supprimer.',
                    'route' => 'admin_planaction_index',
                ],
                [
                    'title' => 'Jalons de progression',
                    'description' => 'Suivre les jalons enregistrés et supprimer les entrées inutiles.',
                    'route' => 'admin_jalonprogression_index',
                ],
                [
                    'title' => 'Planificateurs intelligents',
                    'description' => 'Voir les configurations de planification et les supprimer si besoin.',
                    'route' => 'admin_planificateurintelligent_index',
                ],
            ],
        ]);
    }
}
