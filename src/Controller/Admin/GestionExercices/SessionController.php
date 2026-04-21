<?php
namespace App\Controller\Admin\GestionExercices;

use App\Repository\SessionRepository;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/sessions')]
//#[IsGranted('ROLE_ADMIN')]
final class SessionController extends AbstractController
{
    #[Route('/', name: 'admin_sessions_index')]
    public function index(SessionRepository $sessionRepository): Response
    {
        //$sessions = $sessionRepository->findAllSessionsForAdmin();
        $sessions = [];
        
        return $this->render('admin/gestion_exercices/sessions_index.html.twig', [
            'sessions' => $sessions
        ]);
    }
    
    #[Route('/user/{idU}', name: 'admin_sessions_user')]
    public function userSessions($idU, EntityManagerInterface $entityManager, SessionRepository $sessionRepository): Response
    {
        $user = $entityManager->find(Utilisateur::class, $idU);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        $sessions = $sessionRepository->findSessionsByUser($user);
        
        return $this->render('admin/gestion_exercices/sessions_user.html.twig', [
            'user' => $user,
            'sessions' => $sessions
        ]);
    }
    
    #[Route('/{idSession}', name: 'admin_sessions_show')]
    public function show($idSession, SessionRepository $sessionRepository): Response
    {
        $session = $sessionRepository->find($idSession);
        
        if (!$session) {
            throw $this->createNotFoundException('Session non trouvée');
        }
        
        return $this->render('admin/gestion_exercices/sessions_show.html.twig', [
            'session' => $session
        ]);
    }
}