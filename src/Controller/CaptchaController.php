<?php

namespace App\Controller;

use App\Service\CaptchaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CaptchaController extends AbstractController
{
    #[Route('/captcha/{context}/image', name: 'app_captcha_image', methods: ['GET'])]
    public function image(string $context, Request $request, CaptchaService $captchaService): Response
    {
        $svg = $captchaService->renderSvg($request->getSession(), $context);

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/captcha/{context}/refresh', name: 'app_captcha_refresh', methods: ['POST'])]
    public function refresh(string $context, Request $request, CaptchaService $captchaService): JsonResponse
    {
        $captchaService->refreshChallenge($request->getSession(), $context);

        return $this->json([
            'imageUrl' => $this->generateUrl('app_captcha_image', ['context' => $context]) . '?v=' . rawurlencode((string) microtime(true)),
        ]);
    }

    #[Route('/captcha/{context}/verify', name: 'app_captcha_verify', methods: ['POST'])]
    public function verify(string $context, Request $request, CaptchaService $captchaService): JsonResponse
    {
        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('captcha_verify_' . $context, $csrfToken)) {
            return $this->json([
                'valid' => false,
                'message' => 'Invalid CAPTCHA verification request. Please try again.',
            ], Response::HTTP_FORBIDDEN);
        }

        $captcha = (string) $request->request->get('captcha', '');
        if ($captchaService->verify($request->getSession(), $context, $captcha)) {
            return $this->json([
                'valid' => true,
                'message' => 'CAPTCHA valid.',
            ]);
        }

        return $this->json([
            'valid' => false,
            'message' => 'Invalid CAPTCHA. Please try again.',
            'imageUrl' => $this->generateUrl('app_captcha_image', ['context' => $context]) . '?v=' . rawurlencode((string) microtime(true)),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
