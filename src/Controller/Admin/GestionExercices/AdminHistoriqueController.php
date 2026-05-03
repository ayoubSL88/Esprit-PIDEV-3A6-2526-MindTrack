<?php
namespace App\Controller\Admin\GestionExercices;

use App\Repository\SessionRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/admin/historique')]
#[IsGranted('ROLE_ADMIN')]
final class AdminHistoriqueController extends AbstractController
{
    #[Route('/', name: 'admin_historique_index')]
    public function index(
        Request $request,
        SessionRepository $sessionRepository,
        UtilisateurRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        // Récupérer les paramètres de filtrage
        $userId = $request->query->get('user_id', '');
        $search = $request->query->get('search', '');
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');
        
        // Créer le QueryBuilder avec les filtres
        $qb = $sessionRepository->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e')
            ->where('s.terminee = true');
        
        // Filtrer par utilisateur
        if ($userId) {
            $qb->andWhere('u.idU = :userId')
               ->setParameter('userId', $userId);
        }
        
        // Filtrer par recherche (nom exercice, nom utilisateur)
        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR u.nomU LIKE :search OR u.prenomU LIKE :search')
               ->setParameter('search', "%{$search}%");
        }
        
        // Filtrer par date de début
        if ($dateDebut) {
            $qb->andWhere('s.dateFin >= :dateDebut')
               ->setParameter('dateDebut', new \DateTime($dateDebut));
        }
        
        // Filtrer par date de fin
        if ($dateFin) {
            $qb->andWhere('s.dateFin <= :dateFin')
               ->setParameter('dateFin', new \DateTime($dateFin . ' 23:59:59'));
        }
        
        // Trier par date décroissante
        $qb->orderBy('s.dateFin', 'DESC');
        
        // Pagination (15 par page)
        $sessions = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            15
        );
        
        // Récupérer tous les utilisateurs pour le filtre
        $allUsers = $userRepository->findAll();
        
        // Statistiques globales (filtrées)
        $statsQb = clone $qb;
        $statsQb->select('COUNT(s.idSession) as total')
                ->addSelect('AVG(s.progress) as avgProgress')
                ->addSelect('SUM(s.dureeReelle) as totalTime');
        
        $statsResult = $statsQb->getQuery()->getSingleResult();
        
        $stats = [
            'total' => $statsResult['total'] ?? 0,
            'avg_progress' => round($statsResult['avgProgress'] ?? 0, 1),
            'total_time' => round(($statsResult['totalTime'] ?? 0) / 60),
        ];
        
        return $this->render('admin/gestion_exercices/historique_index.html.twig', [
            'sessions' => $sessions,
            'stats' => $stats,
            'all_users' => $allUsers,
            'selected_user_id' => $userId,
            'search' => $search,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'export_params' => http_build_query([
                'user_id' => $userId,
                'search' => $search,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin
            ])
        ]);
    }
    
    #[Route('/export-pdf', name: 'admin_historique_export_pdf')]
    public function exportPdf(
        Request $request,
        SessionRepository $sessionRepository,
        UtilisateurRepository $userRepository
    ): Response {
        // Récupérer les paramètres de filtrage (comme dans index)
        $userId = $request->query->get('user_id', '');
        $search = $request->query->get('search', '');
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');
        
        // Récupérer l'utilisateur sélectionné
        $selectedUser = null;
        if ($userId) {
            $selectedUser = $userRepository->find($userId);
        }
        
        // Construire la requête des sessions (même logique que index)
        $qb = $sessionRepository->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('s.exercice', 'e')
            ->where('s.terminee = true');
        
        if ($userId) {
            $qb->andWhere('u.idU = :userId')
               ->setParameter('userId', $userId);
        }
        
        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR u.nomU LIKE :search OR u.prenomU LIKE :search')
               ->setParameter('search', "%{$search}%");
        }
        
        if ($dateDebut) {
            $qb->andWhere('s.dateFin >= :dateDebut')
               ->setParameter('dateDebut', new \DateTime($dateDebut));
        }
        
        if ($dateFin) {
            $qb->andWhere('s.dateFin <= :dateFin')
               ->setParameter('dateFin', new \DateTime($dateFin . ' 23:59:59'));
        }
        
        $qb->orderBy('s.dateFin', 'DESC');
        
        $sessions = $qb->getQuery()->getResult();
        
        // Calcul des stats
        $totalSessions = count($sessions);
        $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
        $moyenneProgress = $totalSessions > 0 
            ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
            : 0;
        
        // Générer le HTML pour le PDF
        $html = $this->renderView('admin/gestion_exercices/export_pdf.html.twig', [
            'total_sessions' => $totalSessions,
            'total_temps' => round($totalTemps / 60),
            'moyenne_progress' => round($moyenneProgress, 1),
            'sessions' => $sessions,
            'selected_user' => $selectedUser,
            'search' => $search,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'date' => new \DateTime()
        ]);
        
        // Configuration de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Pour charger les images distantes si besoin
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Format paysage pour mieux voir les colonnes
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admin_historique_' . date('Y-m-d') . '.pdf"' 
        ]);
    }

    #[Route('/{idSession}', name: 'admin_historique_show')]
    public function show(int $idSession, SessionRepository $sessionRepository): Response
    {
        $session = $sessionRepository->find($idSession);
        
        if (!$session) {
            throw $this->createNotFoundException('Session non trouvée');
        }
        
        return $this->render('admin/gestion_exercices/historique_show.html.twig', [
            'session' => $session
        ]);
    }
}