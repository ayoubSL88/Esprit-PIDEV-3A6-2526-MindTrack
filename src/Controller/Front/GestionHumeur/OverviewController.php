<?php

namespace App\Controller\Front\GestionHumeur;

use App\Entity\Humeur;
use App\Entity\Journalemotionnel;
use App\Form\HumeurType;
use App\Form\JournalemotionnelType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/humeur', name: 'front_gestion_humeur_')]
final class OverviewController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $humeurs = $entityManager->getRepository(Humeur::class)->findBy([], ['date' => 'DESC', 'idH' => 'DESC']);

        return $this->render('front/gestion_humeur/index.html.twig', [
            'humeurs' => $humeurs,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $humeur = new Humeur();
        $humeur->setIdH($this->getNextId($entityManager));
        $humeur->setDate(new \DateTimeImmutable('today'));
        $humeur->setTypeHumeur('Neutre');
        $humeur->setIntensite(5);

        $form = $this->createForm(HumeurType::class, $humeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($humeur);
            $entityManager->flush();

            $this->addFlash('success', 'Mood entry created successfully.');

            return $this->redirectToRoute('front_gestion_humeur_index');
        }

        return $this->render('front/gestion_humeur/new.html.twig', [
            'humeur' => $humeur,
            'form' => $form,
        ]);
    }

    #[Route('/{idH}', name: 'show', methods: ['GET'], requirements: ['idH' => '\d+'])]
    public function show(Humeur $humeur): Response
    {
        return $this->render('front/gestion_humeur/show.html.twig', [
            'humeur' => $humeur,
        ]);
    }

    #[Route('/{idH}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['idH' => '\d+'])]
    public function edit(Request $request, Humeur $humeur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HumeurType::class, $humeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Mood entry updated successfully.');

            return $this->redirectToRoute('front_gestion_humeur_index');
        }

        return $this->render('front/gestion_humeur/edit.html.twig', [
            'humeur' => $humeur,
            'form' => $form,
        ]);
    }

    #[Route('/{idH}', name: 'delete', methods: ['POST'], requirements: ['idH' => '\d+'])]
    public function delete(Request $request, Humeur $humeur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_humeur_'.$humeur->getIdH(), (string) $request->request->get('_token'))) {
            $entityManager->remove($humeur);
            $entityManager->flush();
            $this->addFlash('success', 'Mood entry deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid delete token.');
        }

        return $this->redirectToRoute('front_gestion_humeur_index');
    }

    #[Route('/journal', name: 'journal_index', methods: ['GET'])]
    public function journalIndex(EntityManagerInterface $entityManager): Response
    {
        $journals = $entityManager->getRepository(Journalemotionnel::class)
            ->findBy([], ['dateCreation' => 'DESC', 'idJ' => 'DESC']);

        return $this->render('front/gestion_humeur/journal_index.html.twig', [
            'journals' => $journals,
        ]);
    }

    #[Route('/journal/new', name: 'journal_new', methods: ['GET', 'POST'])]
    public function journalNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $journal = new Journalemotionnel();
        $journal->setIdJ($this->getNextJournalId($entityManager));
        $journal->setDateCreation(new \DateTimeImmutable());
        $journal->setNotePersonnelle('');

        $form = $this->createForm(JournalemotionnelType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($journal);
            $entityManager->flush();

            $this->addFlash('success', 'Journal entry created successfully.');

            return $this->redirectToRoute('front_gestion_humeur_journal_index');
        }

        return $this->render('front/gestion_humeur/journal_new.html.twig', [
            'journal' => $journal,
            'form' => $form,
        ]);
    }

    #[Route('/journal/{idJ}', name: 'journal_show', methods: ['GET'], requirements: ['idJ' => '\d+'])]
    public function journalShow(Journalemotionnel $journal): Response
    {
        return $this->render('front/gestion_humeur/journal_show.html.twig', [
            'journal' => $journal,
        ]);
    }

    #[Route('/journal/{idJ}/edit', name: 'journal_edit', methods: ['GET', 'POST'], requirements: ['idJ' => '\d+'])]
    public function journalEdit(Request $request, Journalemotionnel $journal, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JournalemotionnelType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Journal entry updated successfully.');

            return $this->redirectToRoute('front_gestion_humeur_journal_index');
        }

        return $this->render('front/gestion_humeur/journal_edit.html.twig', [
            'journal' => $journal,
            'form' => $form,
        ]);
    }

    #[Route('/journal/{idJ}', name: 'journal_delete', methods: ['POST'], requirements: ['idJ' => '\d+'])]
    public function journalDelete(Request $request, Journalemotionnel $journal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_journal_'.$journal->getIdJ(), (string) $request->request->get('_token'))) {
            $entityManager->remove($journal);
            $entityManager->flush();
            $this->addFlash('success', 'Journal entry deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid delete token.');
        }

        return $this->redirectToRoute('front_gestion_humeur_journal_index');
    }

    private function getNextId(EntityManagerInterface $entityManager): int
    {
        $maxId = $entityManager->createQuery('SELECT COALESCE(MAX(h.idH), 0) FROM App\Entity\Humeur h')
            ->getSingleScalarResult();

        return ((int) $maxId) + 1;
    }

    private function getNextJournalId(EntityManagerInterface $entityManager): int
    {
        $maxId = $entityManager->createQuery('SELECT COALESCE(MAX(j.idJ), 0) FROM App\Entity\Journalemotionnel j')
            ->getSingleScalarResult();

        return ((int) $maxId) + 1;
    }
}
