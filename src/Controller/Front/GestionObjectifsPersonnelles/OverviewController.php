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
        return $this->render('front/gestion_objectifs_personnelles/index.html.twig', [
            'modules' => [
                [
                    'title' => 'Objectifs',
                    'description' => 'Gerer les objectifs personnels avec recherche, tri, filtre et CRUD complet.',
                    'route' => 'front_objectif_index',
                ],
                [
                    'title' => 'Plans d’action',
                    'description' => 'Structurer les etapes prioritaires rattachees a chaque objectif.',
                    'route' => 'front_planaction_index',
                ],
                [
                    'title' => 'Jalons de progression',
                    'description' => 'Suivre les points de controle et les dates cibles de progression.',
                    'route' => 'front_jalonprogression_index',
                ],
                [
                    'title' => 'Planificateurs intelligents',
                    'description' => 'Piloter le mode d’organisation, la capacite et la derniere generation.',
                    'route' => 'front_planificateurintelligent_index',
                ],
            ],
        ]);
    }
}
