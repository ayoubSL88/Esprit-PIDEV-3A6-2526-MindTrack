<?php

namespace App\Controller\Front\GestionObjectifsPersonnelles\Crud;

use App\Entity\Jalonprogression;
use App\Form\JalonprogressionType;
use App\Repository\JalonprogressionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/objectifs/jalons', name: 'front_jalonprogression_')]
final class JalonprogressionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, JalonprogressionRepository $repository): Response
    {
        $filters = $this->getFilters($request);

        return $this->render('front/gestion_objectifs_personnelles/jalonprogression/index.html.twig', [
            'jalons' => $repository->findBySearchSortAndStatus($filters['q'], $filters['sort'], $filters['status']),
            'filters' => $filters,
            'sort_choices' => [
                'Date cible la plus récente' => 'date_desc',
                'Date cible la plus ancienne' => 'date_asc',
                'Date atteinte la plus récente' => 'date_atteinte_desc',
                'Date atteinte la plus ancienne' => 'date_atteinte_asc',
                'Statut A-Z' => 'statut_asc',
                'Statut Z-A' => 'statut_desc',
            ],
            'status_choices' => [
                'En cours' => 'en_cours',
                'Atteint' => 'atteint',
            ],
        ]);
    }

    #[Route('/new', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, JalonprogressionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $jalon = new Jalonprogression();
        $jalon->setIdJalon($repository->nextId());
        $jalon->setDateCible(new \DateTimeImmutable());
        $jalon->setDateAtteinte(new \DateTimeImmutable());
        $jalon->setAtteint(false);
        $jalon->setPourcentageProgression(0);

        $form = $this->createForm(JalonprogressionType::class, $jalon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jalon);
            $entityManager->flush();

            $this->addFlash('success', 'Jalon ajouté avec succès.');

            return $this->redirectToRoute('front_jalonprogression_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/jalonprogression/form.html.twig', [
            'form' => $form,
            'page_title' => 'Ajouter un jalon',
            'submit_label' => 'Créer',
        ]);
    }

    #[Route('/{idJalon}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(int $idJalon, Request $request, JalonprogressionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $jalon = $repository->find($idJalon);

        if (!$jalon instanceof Jalonprogression) {
            throw $this->createNotFoundException('Jalon introuvable.');
        }

        $form = $this->createForm(JalonprogressionType::class, $jalon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Jalon mis à jour avec succès.');

            return $this->redirectToRoute('front_jalonprogression_index');
        }

        return $this->render('front/gestion_objectifs_personnelles/jalonprogression/form.html.twig', [
            'form' => $form,
            'page_title' => 'Modifier un jalon',
            'submit_label' => 'Enregistrer',
            'jalon' => $jalon,
        ]);
    }

    #[Route('/{idJalon}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $idJalon, Request $request, JalonprogressionRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $jalon = $repository->find($idJalon);

        if (!$jalon instanceof Jalonprogression) {
            throw $this->createNotFoundException('Jalon introuvable.');
        }

        if ($this->isCsrfTokenValid('delete_jalon_' . $jalon->getIdJalon(), (string) $request->request->get('_token'))) {
            $entityManager->remove($jalon);
            $entityManager->flush();
            $this->addFlash('success', 'Jalon supprimé avec succès.');
        }

        return $this->redirectToRoute('front_jalonprogression_index');
    }

    private function getFilters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query->get('q', '')),
            'sort' => trim((string) $request->query->get('sort', 'date_desc')),
            'status' => trim((string) $request->query->get('status', '')),
        ];
    }
}
