<?php

namespace App\Controller\Front\GestionExercices;

use App\Entity\Exercice;
use App\Entity\Utilisateur;
use App\Repository\ExerciceRepository;
use App\Repository\SessionRepository;
use App\Service\AIExerciceSuggester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/app/exercices')]
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
                'recent_sessions' => $sessionRepo->findBy(
                    ['user' => $user, 'terminee' => true],
                    ['dateFin' => 'DESC'],
                    5
                ),
            ];
        }

        return $this->render('front/gestion_exercices/home.html.twig', [
            'recent_exercices' => $recentExercices,
            'stats' => $stats,
        ]);
    }

    #[Route('/list', name: 'front_gestion_exercices_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search', '');
        $difficulte = $request->query->get('difficulte', '');
        $sort = $request->query->get('sort', 'nom');
        $order = $request->query->get('order', 'ASC');

        $qb = $entityManager->getRepository(Exercice::class)->createQueryBuilder('e');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($difficulte) {
            $qb->andWhere('e.difficulte = :difficulte')
                ->setParameter('difficulte', $difficulte);
        }

        $qb->orderBy('e.' . $sort, $order);

        $exercices = $qb->getQuery()->getResult();

        return $this->render('front/gestion_exercices/index.html.twig', [
            'exercices' => $exercices,
            'search' => $search,
            'difficulte' => $difficulte,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/{idEx}', name: 'front_gestion_exercices_show', methods: ['GET'])]
    public function show(Exercice $exercice): Response
    {
        return $this->render('front/gestion_exercices/show.html.twig', [
            'exercice' => $exercice,
        ]);
    }

    #[Route('/suggestion/humeur/{mood}', name: 'front_suggestion_by_mood', requirements: ['mood' => '^(10|[1-9])$'])]
    public function suggestionByMood(int $mood, AIExerciceSuggester $aisuggester, Request $request): Response
    {
        $request->getSession()->set('exercise_selected_mood', $mood);
        $exercice = $aisuggester->suggestByMood($mood);

        if (!$exercice) {
            $this->addFlash('error', 'Aucun exercice trouve pour ce niveau d humeur.');

            return $this->redirectToRoute('front_gestion_exercices_home');
        }

        return $this->redirectToRoute('front_gestion_exercices_show', [
            'idEx' => $exercice->getIdEx(),
        ]);
    }

    #[Route('/suggestion/pour-vous', name: 'front_suggestion_for_you')]
    public function suggestionForYou(AIExerciceSuggester $aisuggester, Request $request): Response
    {
        $user = $this->getUser();
        $selectedMood = (int) $request->getSession()->get('exercise_selected_mood', 0);

        if ($selectedMood >= 1 && $selectedMood <= 10) {
            if ($user instanceof Utilisateur) {
                $exercice = $aisuggester->suggestCombined($user, $selectedMood);
            } else {
                $exercice = $aisuggester->suggestByMood($selectedMood);
            }
        } else {
            if ($user instanceof Utilisateur) {
                $exercice = $aisuggester->suggestByHistory($user);
            } else {
                $exercice = $aisuggester->getRandomSuggestion();
            }
        }

        if (!$exercice) {
            $this->addFlash('error', 'Aucun exercice recommande disponible pour le moment.');

            return $this->redirectToRoute('front_gestion_exercices_home');
        }

        return $this->redirectToRoute('front_gestion_exercices_show', [
            'idEx' => $exercice->getIdEx(),
        ]);
    }
}
