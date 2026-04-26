<?php

namespace App\Controller;

use App\Service\Chat\OllamaChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ChatController extends AbstractController
{
    #[Route('/api/chat', name: 'app_chat', methods: ['POST'])]
    public function chat(Request $request, OllamaChatService $ollamaChatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = trim((string) ($data['message'] ?? ''));

        if ($message === '') {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $reply = $ollamaChatService->generateGenericReply($message);

        if ($reply === null) {
            return new JsonResponse([
                'reply' => "Ollama n'est pas accessible. Verifiez que `ollama serve` est lance et que le modele configure est disponible.",
                'source' => 'error',
            ], 503);
        }

        return new JsonResponse([
            'reply' => $reply,
            'source' => 'ollama',
        ]);
    }
}
