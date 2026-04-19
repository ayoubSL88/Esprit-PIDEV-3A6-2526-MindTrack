<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Exception\FaceAuthenticationException;
use App\Repository\UtilisateurRepository;
use App\Service\CompreFaceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    #[Route('/post-login', name: 'app_post_login')]
    public function postLogin(): RedirectResponse
    {
        if ($this->getUser() === null) {
            return $this->redirectToRoute('app_login');
        }

        return $this->isGranted('ROLE_ADMIN')
            ? $this->redirectToRoute('admin_dashboard')
            : $this->redirectToRoute('front_home');
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response|RedirectResponse
    {
        if ($this->getUser() !== null) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin_dashboard')
                : $this->redirectToRoute('front_home');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/login/face', name: 'app_login_face', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        CompreFaceService $compreFaceService,
        Security $security,
    ): JsonResponse {
        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['message' => 'Invalid Face ID request payload.'], Response::HTTP_BAD_REQUEST);
        }

        $csrfToken = (string) ($payload['csrf_token'] ?? '');
        if (!$this->isCsrfTokenValid('face_login', $csrfToken)) {
            return $this->json(['message' => 'The Face ID request is no longer valid. Refresh the page and try again.'], Response::HTTP_FORBIDDEN);
        }

        $faceCapture = (string) ($payload['image'] ?? '');

        if ($faceCapture === '') {
            return $this->json(['message' => 'Face capture is required for Face ID sign in.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $recognition = $compreFaceService->recognizeFace($faceCapture);
        } catch (FaceAuthenticationException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);
        }

        if (!$recognition['matched'] || $recognition['subject'] === '') {
            return $this->json(['message' => 'Face ID did not match any enrolled account.'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Utilisateur|null $user */
        $user = $utilisateurRepository->findOneBy([
            'face_subject' => $recognition['subject'],
            'face_enabled' => true,
        ]);

        if ($user === null) {
            return $this->json(['message' => 'The matched Face ID account is not available in MindTrack.'], Response::HTTP_UNAUTHORIZED);
        }

        $security->login($user, 'form_login', 'main');

        $redirect = in_array('ROLE_ADMIN', $user->getRoles(), true)
            ? $this->generateUrl('admin_dashboard')
            : $this->generateUrl('front_home');

        return $this->json([
            'message' => 'Face ID sign in successful.',
            'redirect' => $redirect,
            'similarity' => $recognition['similarity'],
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
