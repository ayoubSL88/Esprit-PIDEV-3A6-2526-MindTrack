<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    private const OLLAMA_URL = 'http://localhost:11434/api/generate';
    private const OLLAMA_MODEL = 'llama3.2:1b';
    private const SYSTEM_PROMPT = <<<PROMPT
Tu es MindBot, un assistant bienveillant integre dans l'application MindTrack de suivi d'habitudes.
Tu aides l'utilisateur a suivre ses habitudes, a rester motive, a analyser sa progression et a etablir de bonnes routines.
Reponds toujours en francais, de maniere concise et encourageante. Maximum 3 a 4 phrases par reponse.
PROMPT;

    #[Route('/api/chat', name: 'app_chat', methods: ['POST'])]
    public function chat(Request $request): StreamedResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = trim((string) ($data['message'] ?? ''));

        if ($message === '') {
            return new StreamedResponse(function (): void {
                echo json_encode(['error' => 'Message vide']);
            }, 400, ['Content-Type' => 'application/json']);
        }

        $payload = json_encode([
            'model' => self::OLLAMA_MODEL,
            'prompt' => self::SYSTEM_PROMPT . "\n\nUtilisateur: {$message}\nMindBot:",
            'stream' => true,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/x-ndjson\r\n",
                'content' => $payload,
                'timeout' => 120,
            ],
        ]);

        $stream = @fopen(self::OLLAMA_URL, 'r', false, $context);

        $response = new StreamedResponse(function () use ($stream): void {
            if ($stream === false) {
                echo json_encode(['response' => "Ollama n'est pas accessible. Lance `ollama serve` dans un terminal."]);
                return;
            }

            while (!feof($stream)) {
                $line = fgets($stream);
                if ($line !== false && trim($line) !== '') {
                    echo $line;
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            fclose($stream);
        });

        $response->headers->set('Content-Type', 'application/x-ndjson');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
