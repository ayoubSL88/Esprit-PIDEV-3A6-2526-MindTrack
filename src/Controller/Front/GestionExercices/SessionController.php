<?php
namespace App\Controller\Front\GestionExercices;

use App\Entity\Exercice;
use App\Entity\Session;
use App\Repository\ProgressionRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/session')]
final class SessionController extends AbstractController
{
    #[Route('/start/{idEx}', name: 'front_session_start')]
    public function start(Exercice $exercice, EntityManagerInterface $em): Response
    {
        // Créer une nouvelle session
        $session = new Session();
        $session->setExercice($exercice);
        $session->setDateDebut(new \DateTime());
        $session->setTerminee(false);
        $session->setProgress(0);
        $session->setSteps([]);
        
        $em->persist($session);
        $em->flush();
        
        return $this->redirectToRoute('front_session_current', ['idSession' => $session->getIdSession()]);
    }
    
    #[Route('/current/{idSession}', name: 'front_session_current')]
    public function current(Session $session): Response
    {
        $exercice = $session->getExercice();
        
        return $this->render('front/gestion_exercices/session_active.html.twig', [
            'session' => $session,
            'exercice' => $exercice,
        ]);
    }
    
    #[Route('/save-progress/{idSession}', name: 'front_session_save_progress', methods: ['POST'])]
    public function saveProgress(Session $session, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['progress'])) {
            $session->setProgress(min(100, max(0, (int)$data['progress'])));
        }
        
        if (isset($data['steps'])) {
            $currentSteps = $session->getSteps() ?? [];
            $session->setSteps(array_merge($currentSteps, $data['steps']));
        }
        
        $em->flush();
        
        return $this->json(['success' => true, 'progress' => $session->getProgress()]);
    }
    
    #[Route('/finish/{idSession}', name: 'front_session_finish', methods: ['POST'])]
    public function finish(Session $session, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $session->setTerminee(true);
        $session->setDateFin(new \DateTime());
        
        // Calcul de la durée réelle
        $dateDebut = $session->getDateDebut();
        $dateFin = $session->getDateFin();
        $dureeReelle = $dateFin->getTimestamp() - $dateDebut->getTimestamp();
        $session->setDureeReelle($dureeReelle);
        
        if (isset($data['resultat'])) {
            $session->setResultat($data['resultat']);
        }
        
        if (isset($data['commentaires'])) {
            $session->setCommentaires($data['commentaires']);
        }
        
        // Mettre à jour la progression à 100% si terminé
        $session->setProgress(100);
        
        $em->flush();
        
        return $this->json(['success' => true, 'redirect' => $this->generateUrl('front_historique_index')]);
    }
}