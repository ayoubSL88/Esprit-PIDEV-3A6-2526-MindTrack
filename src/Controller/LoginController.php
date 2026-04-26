<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Exception\FaceAuthenticationException;
use App\Exception\GitHubAuthenticationException;
use App\Exception\GoogleAuthenticationException;
use App\Repository\UtilisateurRepository;
use App\Service\CaptchaService;
use App\Service\CompreFaceService;
use App\Service\GitHubOAuthService;
use App\Service\GoogleOAuthService;
use App\Service\TotpService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    private const TWO_FACTOR_PENDING_USER_ID = 'login_2fa_pending_user_id';

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

    #[Route('/login', name: 'app_login', methods: ['GET'])]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, CaptchaService $captchaService): Response|RedirectResponse
    {
        if ($this->getUser() !== null) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin_dashboard')
                : $this->redirectToRoute('front_home');
        }

        $session = $request->getSession();

        return $this->render('security/login.html.twig', [
            'last_username' => (string) $session->get('last_login_email', $authenticationUtils->getLastUsername()),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'captcha_verified' => $captchaService->isVerified($session, 'login'),
        ]);
    }

    #[Route('/login/password', name: 'app_login_password', methods: ['POST'])]
    public function passwordLogin(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        CaptchaService $captchaService,
    ): RedirectResponse {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_post_login');
        }

        $session = $request->getSession();
        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('authenticate', $csrfToken)) {
            $this->addFlash('error', 'Invalid login form token. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        if (!$captchaService->isVerified($session, 'login')) {
            $this->addFlash('error', 'Please verify the CAPTCHA first.');

            return $this->redirectToRoute('app_login');
        }

        $email = trim((string) $request->request->get('_username', ''));
        $password = (string) $request->request->get('_password', '');
        $session->set('last_login_email', $email);

        /** @var Utilisateur|null $user */
        $user = $utilisateurRepository->findOneBy(['emailU' => $email]);
        if ($user === null || !$passwordHasher->isPasswordValid($user, $password)) {
            $this->addFlash('error', 'Invalid email or password.');

            return $this->redirectToRoute('app_login');
        }

        if ($this->requiresTwoFactor($user)) {
            return $this->startTwoFactorChallenge($request, $user);
        }

        $session->remove('last_login_email');
        $captchaService->clearVerified($session, 'login');
        $security->login($user, 'form_login', 'main');

        return $this->redirectToRoute('app_post_login');
    }

    #[Route('/connect/google', name: 'app_connect_google', methods: ['GET'])]
    public function connectGoogle(Request $request, GoogleOAuthService $googleOAuthService): RedirectResponse
    {
        try {
            $state = bin2hex(random_bytes(16));
            $request->getSession()->set('google_oauth_state', $state);
            $redirectUri = $this->generateUrl('app_connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->redirect($googleOAuthService->getAuthorizationUrl($state, $redirectUri));
        } catch (GoogleAuthenticationException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/google/check', name: 'app_connect_google_check', methods: ['GET'])]
    public function connectGoogleCheck(
        Request $request,
        GoogleOAuthService $googleOAuthService,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        Connection $connection,
        Security $security,
    ): RedirectResponse {
        $session = $request->getSession();
        $expectedState = (string) $session->get('google_oauth_state', '');
        $session->remove('google_oauth_state');

        $state = (string) $request->query->get('state', '');
        $error = trim((string) $request->query->get('error', ''));

        if ($error !== '') {
            $this->addFlash('error', 'Google sign-in was cancelled or denied.');

            return $this->redirectToRoute('app_login');
        }

        if ($expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->addFlash('error', 'Google sign-in could not be verified. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $redirectUri = $this->generateUrl('app_connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $googleUser = $googleOAuthService->fetchUser((string) $request->query->get('code', ''), $redirectUri);
        } catch (GoogleAuthenticationException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }

        /** @var Utilisateur|null $user */
        $user = $utilisateurRepository->findOneBy(['emailU' => $googleUser['email']]);

        if ($user === null) {
            $nextUserId = (int) $connection->fetchOne('SELECT COALESCE(MAX(id_u), 0) + 1 FROM utilisateur');
            $user = new Utilisateur();
            $user->setIdU($nextUserId);
            $user->setNomU($googleUser['family_name'] !== '' ? $googleUser['family_name'] : 'Google');
            $user->setPrenomU($googleUser['given_name'] !== '' ? $googleUser['given_name'] : 'User');
            $user->setEmailU($googleUser['email']);
            $user->setAgeU(18);
            $user->setRoleU('USER');
            $user->setFace_subject('');
            $user->setFace_image_id('');
            $user->setFace_enabled(false);
            $user->setProfile_picture_path($googleUser['picture']);
            $user->setTotp_secret('');
            $user->setTotp_enabled(false);
            $user->setMdpsU(password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT) ?: bin2hex(random_bytes(24)));
            $entityManager->persist($user);
        } else {
            if ($user->getProfile_picture_path() === '' && $googleUser['picture'] !== '') {
                $user->setProfile_picture_path($googleUser['picture']);
            }
        }

        $entityManager->flush();

        if ($this->requiresTwoFactor($user)) {
            return $this->startTwoFactorChallenge($request, $user);
        }

        $security->login($user, 'form_login', 'main');

        return $this->redirectToRoute('app_post_login');
    }

    #[Route('/connect/github', name: 'app_connect_github', methods: ['GET'])]
    public function connectGithub(Request $request, GitHubOAuthService $gitHubOAuthService): RedirectResponse
    {
        try {
            $state = bin2hex(random_bytes(16));
            $request->getSession()->set('github_oauth_state', $state);
            $redirectUri = $this->generateUrl('app_connect_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->redirect($gitHubOAuthService->getAuthorizationUrl($state, $redirectUri));
        } catch (GitHubAuthenticationException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/connect/github/check', name: 'app_connect_github_check', methods: ['GET'])]
    public function connectGithubCheck(
        Request $request,
        GitHubOAuthService $gitHubOAuthService,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        Connection $connection,
        Security $security,
    ): RedirectResponse {
        $session = $request->getSession();
        $expectedState = (string) $session->get('github_oauth_state', '');
        $session->remove('github_oauth_state');

        $state = (string) $request->query->get('state', '');
        $error = trim((string) $request->query->get('error', ''));

        if ($error !== '') {
            $this->addFlash('error', 'GitHub sign-in was cancelled or denied.');

            return $this->redirectToRoute('app_login');
        }

        if ($expectedState === '' || !hash_equals($expectedState, $state)) {
            $this->addFlash('error', 'GitHub sign-in could not be verified. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $redirectUri = $this->generateUrl('app_connect_github_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $gitHubUser = $gitHubOAuthService->fetchUser((string) $request->query->get('code', ''), $redirectUri);
        } catch (GitHubAuthenticationException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }

        /** @var Utilisateur|null $user */
        $user = $utilisateurRepository->findOneBy(['emailU' => $gitHubUser['email']]);

        if ($user === null) {
            $nextUserId = (int) $connection->fetchOne('SELECT COALESCE(MAX(id_u), 0) + 1 FROM utilisateur');
            $user = new Utilisateur();
            $user->setIdU($nextUserId);
            $user->setNomU($gitHubUser['family_name'] !== '' ? $gitHubUser['family_name'] : 'GitHub');
            $user->setPrenomU($gitHubUser['given_name'] !== '' ? $gitHubUser['given_name'] : ($gitHubUser['login'] !== '' ? $gitHubUser['login'] : 'User'));
            $user->setEmailU($gitHubUser['email']);
            $user->setAgeU(18);
            $user->setRoleU('USER');
            $user->setFace_subject('');
            $user->setFace_image_id('');
            $user->setFace_enabled(false);
            $user->setProfile_picture_path($gitHubUser['picture']);
            $user->setTotp_secret('');
            $user->setTotp_enabled(false);
            $user->setMdpsU(password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT) ?: bin2hex(random_bytes(24)));
            $entityManager->persist($user);
        } else {
            if ($user->getProfile_picture_path() === '' && $gitHubUser['picture'] !== '') {
                $user->setProfile_picture_path($gitHubUser['picture']);
            }
        }

        $entityManager->flush();

        if ($this->requiresTwoFactor($user)) {
            return $this->startTwoFactorChallenge($request, $user);
        }

        $security->login($user, 'form_login', 'main');

        return $this->redirectToRoute('app_post_login');
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

        if ($this->requiresTwoFactor($user)) {
            $this->storeTwoFactorPendingUser($request, $user);

            return $this->json([
                'message' => 'Face verified. Enter your authenticator code to finish signing in.',
                'redirect' => $this->generateUrl('app_login_2fa'),
                'requires_two_factor' => true,
            ]);
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

    #[Route('/login/2fa', name: 'app_login_2fa', methods: ['GET'])]
    public function twoFactor(Request $request, UtilisateurRepository $utilisateurRepository, TotpService $totpService): Response|RedirectResponse
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_post_login');
        }

        $pendingUser = $this->getPendingTwoFactorUser($request, $utilisateurRepository);
        if ($pendingUser === null) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/two_factor.html.twig', [
            'seconds_remaining' => $totpService->getSecondsRemaining(),
        ]);
    }

    #[Route('/login/2fa/verify', name: 'app_login_2fa_verify', methods: ['POST'])]
    public function verifyTwoFactor(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        TotpService $totpService,
        Security $security,
    ): RedirectResponse {
        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('login_2fa_verify', $csrfToken)) {
            $this->addFlash('error', 'Invalid two-factor form token. Please try again.');

            return $this->redirectToRoute('app_login_2fa');
        }

        $pendingUser = $this->getPendingTwoFactorUser($request, $utilisateurRepository);
        if ($pendingUser === null) {
            $this->addFlash('error', 'Your sign-in session expired. Please sign in again.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->requiresTwoFactor($pendingUser)) {
            $this->clearTwoFactorChallenge($request);
            $security->login($pendingUser, 'form_login', 'main');

            return $this->redirectToRoute('app_post_login');
        }

        $code = (string) $request->request->get('code', '');
        if (!$totpService->verifyCode($pendingUser->getTotp_secret(), $code)) {
            $this->addFlash('error', 'Invalid authenticator code.');

            return $this->redirectToRoute('app_login_2fa');
        }

        $this->clearTwoFactorChallenge($request);
        $request->getSession()->remove('last_login_email');
        $security->login($pendingUser, 'form_login', 'main');

        return $this->redirectToRoute('app_post_login');
    }

    #[Route('/login/2fa/cancel', name: 'app_login_2fa_cancel', methods: ['GET'])]
    public function cancelTwoFactor(Request $request): RedirectResponse
    {
        $this->clearTwoFactorChallenge($request);
        $this->addFlash('error', 'Two-factor sign-in was cancelled.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function requiresTwoFactor(Utilisateur $user): bool
    {
        return $user->getTotp_enabled() && trim($user->getTotp_secret()) !== '';
    }

    private function startTwoFactorChallenge(Request $request, Utilisateur $user): RedirectResponse
    {
        $this->storeTwoFactorPendingUser($request, $user);
        $this->addFlash('success', 'Primary sign-in complete. Enter your authenticator code to continue.');

        return $this->redirectToRoute('app_login_2fa');
    }

    private function storeTwoFactorPendingUser(Request $request, Utilisateur $user): void
    {
        $request->getSession()->set(self::TWO_FACTOR_PENDING_USER_ID, $user->getIdU());
    }

    private function clearTwoFactorChallenge(Request $request): void
    {
        $request->getSession()->remove(self::TWO_FACTOR_PENDING_USER_ID);
    }

    private function getPendingTwoFactorUser(Request $request, UtilisateurRepository $utilisateurRepository): ?Utilisateur
    {
        $pendingUserId = $request->getSession()->get(self::TWO_FACTOR_PENDING_USER_ID);
        if (!is_int($pendingUserId) && !ctype_digit((string) $pendingUserId)) {
            return null;
        }

        $user = $utilisateurRepository->find((int) $pendingUserId);

        return $user instanceof Utilisateur ? $user : null;
    }
}
