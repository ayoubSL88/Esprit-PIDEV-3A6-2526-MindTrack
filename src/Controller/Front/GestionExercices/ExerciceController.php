<?php

namespace App\Controller\Front\GestionExercices;

use App\Entity\Exercice;
use App\Form\ExerciceType;
use App\Entity\Utilisateur;
use App\Service\AIExerciceSuggester;
use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;  // ✅ AJOUT IMPORTANT !
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/exercices')]
//#[IsGranted('ROLE_USER')]
final class ExerciceController extends AbstractController
{
    #[Route('/', name: 'front_gestion_exercices_home')]
    public function home(ExerciceRepository $exerciceRepo, SessionRepository $sessionRepo): Response
    {   
        $user = $this->getUser();
        
        $recentExercices = $exerciceRepo->findBy([], ['date_creation' => 'DESC'], 6);
        
        $stats = null;
        if ($user) {
            $sessions = $sessionRepo->findBy(['user' => $user, 'terminee' => true]);
            $totalSessions = count($sessions);
            $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
            $moyenneProgress = $totalSessions > 0 
                ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
                : 0;
            
            $stats = [
                'total' => $totalSessions,
                'temps' => round($totalTemps / 60),
                'moyenne' => round($moyenneProgress, 1),
                'recent_sessions' => $sessionRepo->findBy(['user' => $user, 'terminee' => true], ['dateFin' => 'DESC'], 5)
            ];
        }
        
        return $this->render('front/gestion_exercices/home.html.twig', [
            'recent_exercices' => $recentExercices,
            'stats' => $stats
        ]);
    }

    #[Route('/list', name: 'front_gestion_exercices_index', methods: ['GET'])]
    public function index(Request $request, ExerciceRepository $exerciceRepository, PaginatorInterface $paginator): Response
    {
        // Récupérer les paramètres de filtrage
        $search = $request->query->get('search', '');
        $difficulte = $request->query->get('difficulte', '');
        $sort = $request->query->get('sort', 'nom');
        $order = $request->query->get('order', 'ASC');
        
        // Créer le QueryBuilder
        $qb = $exerciceRepository->createQueryBuilder('e');

        // Filtre par recherche (nom ou type)
        if ($search) {
            $qb->andWhere("e.nom LIKE :search OR e.type LIKE :search")
               ->setParameter('search', "%{$search}%");
        }
        
        // Filtre par difficulté
        if ($difficulte) {
            $qb->andWhere('e.difficulte = :difficulte')
               ->setParameter('difficulte', $difficulte);
        }
        
        // Trier par nom et par ordre
        $qb->orderBy("e.{$sort}", $order);
        
        // Pagination (12 par page)
        $exercices = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            12
        );
        
        return $this->render('front/gestion_exercices/index.html.twig', [
            'exercices' => $exercices,
            'search' => $search,
            'difficulte' => $difficulte,
            'sort' => $sort,
            'order' => $order
        ]);
    }

    #[Route('/{idEx}', name: 'front_gestion_exercices_show', methods: ['GET'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('front/gestion_exercices/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route('/suggestion/humeur/{mood}', name: 'front_suggestion_by_mood')]
    public function suggestionByMood(int $mood, AIExerciceSuggester $aisuggester): Response
    {
        $exercice = $aisuggester->suggestByMood($mood);
        
        if (!$exercice) {
            throw $this->createNotFoundException('Aucun exercice trouvé');
        }
        
        // Rediriger vers la page de l'exercice
        return $this->redirectToRoute('front_gestion_exercices_show', [
            'idEx' => $exercice->getIdEx()
        ]);
    }

    #[Route('/suggestion/pour-vous', name: 'front_suggestion_for_you')]
    public function suggestionForYou(AIExerciceSuggester $aisuggester): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof Utilisateur) {
            // Utilisateur non connecté -> suggestion aléatoire
            $exercice = $aisuggester->getRandomSuggestion();
        } else {
            // Utilisateur connecté -> suggestion basée sur historique
            $exercice = $aisuggester->suggestByHistory($user);
        }
        
        if (!$exercice) {
            throw $this->createNotFoundException('Aucun exercice trouvé');
        }
        
        return $this->redirectToRoute('front_gestion_exercices_show', [
            'idEx' => $exercice->getIdEx()
        ]);
    }
}