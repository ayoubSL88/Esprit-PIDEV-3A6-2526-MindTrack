<?php
namespace App\Controller\Front\GestionExercices;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Service\AdviceApiService;
use App\Service\AIExerciceSuggester;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/app/progression')]
//#[IsGranted('ROLE_USER')]
final class ProgressionController extends AbstractController
{
    #[Route('/', name: 'front_progression_index')]
    public function index(SessionRepository $sessionRepository, AdviceApiService $adviceApi): Response
    {
        //$user = $this->getUser();
        $sessions = $sessionRepository->findBy(['terminee' => true]);
        
        // Calcul des stats
        $totalSessions = count($sessions);
        $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
        $moyenneProgress = $totalSessions > 0 
            ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
            : 0;
        
        // Progression par exercice
        $sessionsByExercice = [];
        foreach ($sessions as $session) {
            $exerciceId = $session->getExercice()->getIdEx();
            if (!isset($sessionsByExercice[$exerciceId])) {
                $sessionsByExercice[$exerciceId] = [
                    'exercice' => $session->getExercice(),
                    'total_sessions' => 0,
                    'moyenne_progression' => 0,
                    'progress_sum' => 0
                ];
            }
            $sessionsByExercice[$exerciceId]['total_sessions']++;
            $sessionsByExercice[$exerciceId]['progress_sum'] += $session->getProgress() ?? 0;
        }
        
        foreach ($sessionsByExercice as &$item) {
            $item['moyenne_progression'] = round($item['progress_sum'] / $item['total_sessions'], 1);
        }
        
        // Données pour le graphique (7 derniers jours)
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $labels[] = $date->format('d/m');
            $count = 0;
            foreach ($sessions as $session) {
                if ($session->getDateFin() && $session->getDateFin()->format('Y-m-d') === $date->format('Y-m-d')) {
                    $count++;
                }
            }
            $data[] = $count;
        }
        
        // Récupérer un conseil aléatoire
        $advice = $adviceApi->getRandomAdvice();
        
        return $this->render('front/gestion_exercices/progression.html.twig', [
            'global_progression' => ['sessionsTerminees' => $totalSessions, 'tempsTotal' => $totalTemps],
            'sessions_by_exercice' => array_values($sessionsByExercice),
            'evolution' => ['labels' => $labels, 'data' => $data],
            'advice' => $advice
        ]);
    }

    #[Route('/export-pdf', name: 'front_progression_export_pdf')]
    public function exportPdf(SessionRepository $sessionRepository): Response
    {
        $user = $this->getUser();
        $sessions = $sessionRepository->findBy(['user' => $user, 'terminee' => true]);
        
        // Calcul des stats
        $totalSessions = count($sessions);
        $totalTemps = array_sum(array_map(fn($s) => $s->getDureeReelle() ?? 0, $sessions));
        $moyenneProgress = $totalSessions > 0 
            ? array_sum(array_map(fn($s) => $s->getProgress() ?? 0, $sessions)) / $totalSessions 
            : 0;
        
        $html = $this->renderView('front/gestion_exercices/export_pdf.html.twig', [
            'total_sessions' => $totalSessions,
            'total_temps' => round($totalTemps / 60),
            'moyenne_progress' => round($moyenneProgress, 1),
            'sessions' => $sessions,
            'user' => $user,
            'date' => new \DateTime()
        ]);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="progression_' . date('Y-m-d') . '.pdf"'
        ]);
    }

    #[Route('/suggestion/pour-vous', name: 'front_suggestion_for_you')]
    public function suggestionForYou(AIExerciceSuggester $aisuggester): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            $exercice = $aisuggester->getRandomSuggestion();
        } else {
            $exercice = $aisuggester->suggestByHistory($user);
        }
        
        if (!$exercice) {
            $this->addFlash('error', 'Aucun exercice recommandé disponible');
            return $this->redirectToRoute('front_gestion_exercices_home');
        }
        
        return $this->redirectToRoute('front_gestion_exercices_show', [
            'idEx' => $exercice->getIdEx()
        ]);
    }

    #[Route('/suggestion/humeur/{mood}', name: 'front_suggestion_by_mood')]
    public function suggestByMood(int $mood, AIExerciceSuggester $aisuggester): Response
    {
        $exercice = $aisuggester->suggestByMood($mood);
        
        if (!$exercice) {
            $this->addFlash('error', 'Aucun exercice trouvé pour cette humeur');
            return $this->redirectToRoute('front_gestion_exercices_home');
        }
        
        return $this->redirectToRoute('front_gestion_exercices_show', [
            'idEx' => $exercice->getIdEx()
        ]);
    }
}