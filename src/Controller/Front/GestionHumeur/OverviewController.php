<?php

namespace App\Controller\Front\GestionHumeur;

use App\Entity\Humeur;
use App\Entity\Journalemotionnel;
use App\Form\HumeurType;
use App\Form\JournalemotionnelType;
use App\Service\GestionHumeur\EmotionDetectionService;
use App\Service\GestionHumeur\HumeurAnalyticsService;
use App\Service\GestionHumeur\JournalAttachmentManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/humeur', name: 'front_gestion_humeur_')]
final class OverviewController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, HumeurAnalyticsService $humeurAnalyticsService): Response
    {
        $humeurs = $entityManager->getRepository(Humeur::class)->findBy([], ['date' => 'DESC', 'idH' => 'DESC']);

        return $this->render('front/gestion_humeur/index.html.twig', [
            'humeurs' => $humeurs,
            'moodAnalysis' => $humeurAnalyticsService->analyze($humeurs),
            'editableMoodIds' => $this->extractEditableMoodIds($humeurs),
        ]);
    }

    #[Route('/detect', name: 'detect', methods: ['GET'])]
    public function detect(): Response
    {
        return $this->render('front/gestion_humeur/detect.html.twig');
    }

    #[Route('/detect/check', name: 'detect_check', methods: ['POST'])]
    public function detectCheck(
        Request $request,
        EntityManagerInterface $entityManager,
        EmotionDetectionService $emotionDetectionService
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json([
                'message' => 'The camera payload is invalid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $token = (string) ($payload['_token'] ?? '');
        if (!$this->isCsrfTokenValid('detect_mood', $token)) {
            return $this->json([
                'message' => 'The mood detection request is no longer valid. Refresh the page and try again.',
            ], Response::HTTP_FORBIDDEN);
        }

        $images = [];
        if (isset($payload['images']) && is_array($payload['images'])) {
            $images = array_values(array_filter($payload['images'], static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
        } elseif (isset($payload['image']) && is_string($payload['image']) && trim($payload['image']) !== '') {
            $images = [(string) $payload['image']];
        }

        if ($images === []) {
            return $this->json([
                'message' => 'No camera frames were captured.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $detectedMood = $emotionDetectionService->detectFromDataUris($images);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => $this->buildUnexpectedDetectionMessage($exception),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $humeur = new Humeur();
        $humeur->setIdH($this->getNextId($entityManager));
        $humeur->setDate(new \DateTime('today'));
        $humeur->setTypeHumeur($detectedMood['type']);
        $humeur->setIntensite($detectedMood['intensity']);

        try {
            $entityManager->persist($humeur);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return $this->json([
                'message' => 'Another mood entry was saved at the same time. Please check your mood again.',
            ], Response::HTTP_CONFLICT);
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => $this->buildUnexpectedDetectionMessage($exception),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'message' => 'Mood detected and saved successfully.',
            'saved' => true,
            'mood' => [
                'type' => $detectedMood['type'],
                'label' => $detectedMood['label'],
                'intensity' => $detectedMood['intensity'],
                'confidence' => $detectedMood['confidence'],
                'summary' => $detectedMood['summary'],
                'framesAnalyzed' => $detectedMood['framesAnalyzed'],
            ],
            'entry' => [
                'idH' => $humeur->getIdH(),
                'date' => $humeur->getDate()?->format('Y-m-d'),
                'showUrl' => $this->generateUrl('front_gestion_humeur_show', [
                    'idH' => $humeur->getIdH(),
                ]),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $humeur = new Humeur();
        $humeur->setIdH($this->getNextId($entityManager));
        $humeur->setDate(new \DateTime('today'));
        $humeur->setTypeHumeur('neutural');
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
            'canEdit' => $this->isEditableWithinOneDay($humeur->getDate()),
        ]);
    }

    #[Route('/{idH}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['idH' => '\d+'])]
    public function edit(Request $request, Humeur $humeur, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isEditableWithinOneDay($humeur->getDate())) {
            $this->addFlash('error', 'You can only edit a mood entry during the day it was created and the following day.');

            return $this->redirectToRoute('front_gestion_humeur_show', [
                'idH' => $humeur->getIdH(),
            ]);
        }

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
            'editableJournalIds' => $this->extractEditableJournalIds($journals),
        ]);
    }

    #[Route('/journal/new', name: 'journal_new', methods: ['GET', 'POST'])]
    public function journalNew(
        Request $request,
        EntityManagerInterface $entityManager,
        JournalAttachmentManager $journalAttachmentManager,
    ): Response
    {
        $journal = new Journalemotionnel();
        $journal->setIdJ($this->getNextJournalId($entityManager));
        $journal->setDateCreation(new \DateTime());
        $journal->setNotePersonnelle('');

        $form = $this->createForm(JournalemotionnelType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->updateJournalAttachmentsFromForm($form, $journal, $journalAttachmentManager);
            } catch (\RuntimeException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }

            if (0 === count($form->getErrors(true))) {
                $entityManager->persist($journal);
                $entityManager->flush();

                $this->addFlash('success', 'Journal entry created successfully.');

                return $this->redirectToRoute('front_gestion_humeur_journal_index');
            }
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
            'canEdit' => $this->isEditableWithinOneDay($journal->getDateCreation()),
        ]);
    }

    #[Route('/journal/{idJ}/edit', name: 'journal_edit', methods: ['GET', 'POST'], requirements: ['idJ' => '\d+'])]
    public function journalEdit(
        Request $request,
        Journalemotionnel $journal,
        EntityManagerInterface $entityManager,
        JournalAttachmentManager $journalAttachmentManager,
    ): Response
    {
        if (!$this->isEditableWithinOneDay($journal->getDateCreation())) {
            $this->addFlash('error', 'You can only edit a journal entry during the day it was created and the following day.');

            return $this->redirectToRoute('front_gestion_humeur_journal_show', [
                'idJ' => $journal->getIdJ(),
            ]);
        }

        $form = $this->createForm(JournalemotionnelType::class, $journal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->updateJournalAttachmentsFromForm($form, $journal, $journalAttachmentManager);
            } catch (\RuntimeException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }

            if (0 === count($form->getErrors(true))) {
                $entityManager->flush();

                $this->addFlash('success', 'Journal entry updated successfully.');

                return $this->redirectToRoute('front_gestion_humeur_journal_index');
            }
        }

        return $this->render('front/gestion_humeur/journal_edit.html.twig', [
            'journal' => $journal,
            'form' => $form,
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

    private function isEditableWithinOneDay(?\DateTimeInterface $date): bool
    {
        if ($date === null) {
            return false;
        }

        return \DateTimeImmutable::createFromInterface($date) >= $this->getEditCutoffDate();
    }

    private function getEditCutoffDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('yesterday 00:00:00');
    }

    private function buildUnexpectedDetectionMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        $debug = (bool) $this->getParameter('kernel.debug');

        if ($debug && $message !== '') {
            return $message;
        }

        return 'The mood detector could not complete the analysis right now.';
    }

    private function updateJournalAttachmentsFromForm(
        FormInterface $form,
        Journalemotionnel $journal,
        JournalAttachmentManager $journalAttachmentManager,
    ): void {
        /** @var UploadedFile|null $screenshotFile */
        $screenshotFile = $form->get('screenshotFile')->getData();
        /** @var UploadedFile|null $audioFile */
        $audioFile = $form->get('audioFile')->getData();

        $journalAttachmentManager->updateJournalAttachments(
            $journal,
            $screenshotFile,
            $audioFile,
            (bool) $form->get('removeScreenshot')->getData(),
            (bool) $form->get('removeAudio')->getData(),
        );
    }

    /**
     * @param Humeur[] $humeurs
     * @return int[]
     */
    private function extractEditableMoodIds(array $humeurs): array
    {
        $editableIds = [];

        foreach ($humeurs as $humeur) {
            if ($this->isEditableWithinOneDay($humeur->getDate())) {
                $editableIds[] = $humeur->getIdH();
            }
        }

        return $editableIds;
    }

    /**
     * @param Journalemotionnel[] $journals
     * @return int[]
     */
    private function extractEditableJournalIds(array $journals): array
    {
        $editableIds = [];

        foreach ($journals as $journal) {
            if ($this->isEditableWithinOneDay($journal->getDateCreation())) {
                $editableIds[] = $journal->getIdJ();
            }
        }

        return $editableIds;
    }
}
