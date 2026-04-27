<?php

namespace App\Controller\Admin\GestionHumeur;

use App\Entity\Humeur;
use App\Entity\Journalemotionnel;
use App\Service\GestionHumeur\HumeurAnalyticsService;
use App\Service\GestionHumeur\JournalAttachmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/humeur', name: 'admin_gestion_humeur_')]
final class OverviewController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, HumeurAnalyticsService $humeurAnalyticsService): Response
    {
        $humeurs = $entityManager->getRepository(Humeur::class)->findBy([], ['date' => 'DESC', 'idH' => 'DESC']);

        return $this->render('admin/gestion_humeur/index.html.twig', [
            'humeurs' => $humeurs,
            'moodAnalysis' => $humeurAnalyticsService->analyze($humeurs),
        ]);
    }

    #[Route('/{idH}', name: 'show', methods: ['GET'], requirements: ['idH' => '\d+'])]
    public function show(Humeur $humeur): Response
    {
        return $this->render('admin/gestion_humeur/show.html.twig', [
            'humeur' => $humeur,
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

        return $this->redirectToRoute('admin_gestion_humeur_index');
    }

    #[Route('/journal', name: 'journal_index', methods: ['GET'])]
    public function journalIndex(EntityManagerInterface $entityManager): Response
    {
        $journals = $entityManager->getRepository(Journalemotionnel::class)
            ->findBy([], ['dateCreation' => 'DESC', 'idJ' => 'DESC']);

        return $this->render('admin/gestion_humeur/journal_index.html.twig', [
            'journals' => $journals,
        ]);
    }

    #[Route('/journal/{idJ}', name: 'journal_show', methods: ['GET'], requirements: ['idJ' => '\d+'])]
    public function journalShow(Journalemotionnel $journal): Response
    {
        return $this->render('admin/gestion_humeur/journal_show.html.twig', [
            'journal' => $journal,
        ]);
    }

    #[Route('/journal/{idJ}', name: 'journal_delete', methods: ['POST'], requirements: ['idJ' => '\d+'])]
    public function journalDelete(
        Request $request,
        Journalemotionnel $journal,
        EntityManagerInterface $entityManager,
        JournalAttachmentManager $journalAttachmentManager,
    ): Response
    {
        if ($this->isCsrfTokenValid('delete_journal_'.$journal->getIdJ(), (string) $request->request->get('_token'))) {
            $journalAttachmentManager->removeJournalAttachments($journal);
            $entityManager->remove($journal);
            $entityManager->flush();
            $this->addFlash('success', 'Journal entry deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid delete token.');
        }

        return $this->redirectToRoute('admin_gestion_humeur_journal_index');
    }
}
