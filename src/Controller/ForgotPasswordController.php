<?php

namespace App\Controller;

use App\Service\CaptchaService;
use App\Service\GestionUser\ValidationService;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForgotPasswordController extends AbstractController
{
    private const SESSION_EMAIL = 'password_reset_email';
    private const SESSION_VERIFIED_EMAIL = 'password_reset_verified_email';
    private const SESSION_VERIFIED_CODE = 'password_reset_verified_code';
    private const SESSION_REQUEST_CAPTCHA = 'password_reset_request_captcha';

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function requestReset(Request $request, PasswordResetService $passwordResetService, CaptchaService $captchaService): Response|RedirectResponse
    {
        $session = $request->getSession();
        $formData = [
            'email' => (string) $session->get(self::SESSION_EMAIL, ''),
        ];
        $fieldErrors = [];

        if ($request->isMethod('POST')) {
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('forgot_password_request', $csrfToken)) {
                $this->addFlash('error', 'Invalid password reset request. Please try again.');

                return $this->redirectToRoute('app_forgot_password');
            }

            $email = strtolower(trim((string) $request->request->get('email', '')));
            $formData['email'] = $email;

            if ($email === '') {
                $fieldErrors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'] = 'Please provide a valid email address.';
            }

            if (!$captchaService->isVerified($session, 'forgot_password')) {
                $fieldErrors['captcha'] = 'Please verify the CAPTCHA first.';
            }

            if ($fieldErrors === []) {
                $session->set(self::SESSION_EMAIL, $email);
                $session->remove(self::SESSION_REQUEST_CAPTCHA);
                $captchaService->clearVerified($session, 'forgot_password');

                $status = $passwordResetService->requestReset($email);
                if ($status === PasswordResetService::REQUEST_RATE_LIMITED) {
                    $this->addFlash('error', 'Please wait before requesting another reset code.');
                } else {
                    $this->addFlash('success', 'If an account exists for that email, a reset code has been sent.');
                }

                return $this->redirectToRoute('app_forgot_password_verify');
            }

        }

        return $this->render('security/forgot_password_request.html.twig', [
            'form' => $formData,
            'fieldErrors' => $fieldErrors,
            'captcha_verified' => $captchaService->isVerified($session, 'forgot_password'),
        ]);
    }

    #[Route('/forgot-password/verify', name: 'app_forgot_password_verify', methods: ['GET', 'POST'])]
    public function verifyReset(Request $request, PasswordResetService $passwordResetService): Response|RedirectResponse
    {
        $email = (string) $request->getSession()->get(self::SESSION_EMAIL, '');
        if ($email === '') {
            return $this->redirectToRoute('app_forgot_password');
        }

        $fieldErrors = [];
        $formData = [
            'email' => $email,
            'code' => '',
        ];

        if ($request->isMethod('POST')) {
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('forgot_password_verify', $csrfToken)) {
                $this->addFlash('error', 'Invalid reset verification request. Please try again.');

                return $this->redirectToRoute('app_forgot_password_verify');
            }

            $code = preg_replace('/\s+/', '', (string) $request->request->get('code', '')) ?? '';
            $formData['code'] = $code;

            if ($code === '') {
                $fieldErrors['code'] = 'Reset code is required.';
            } elseif (!preg_match('/^\d{6}$/', $code)) {
                $fieldErrors['code'] = 'Reset code must contain exactly 6 digits.';
            }

            if ($fieldErrors === []) {
                $status = $passwordResetService->verifyCode($email, $code);

                if ($status === PasswordResetService::STATUS_OK) {
                    $request->getSession()->set(self::SESSION_VERIFIED_EMAIL, $email);
                    $request->getSession()->set(self::SESSION_VERIFIED_CODE, $code);

                    return $this->redirectToRoute('app_forgot_password_reset');
                }

                $fieldErrors['code'] = match ($status) {
                    PasswordResetService::STATUS_EXPIRED => 'This reset code has expired. Request a new one.',
                    PasswordResetService::STATUS_TOO_MANY_ATTEMPTS => 'Too many invalid attempts. Request a new reset code.',
                    PasswordResetService::STATUS_NOT_FOUND => 'No active reset code was found. Request a new one.',
                    default => 'The reset code is invalid.',
                };
            }
        }

        return $this->render('security/forgot_password_verify.html.twig', [
            'form' => $formData,
            'fieldErrors' => $fieldErrors,
        ]);
    }

    #[Route('/forgot-password/reset', name: 'app_forgot_password_reset', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        PasswordResetService $passwordResetService,
        ValidationService $validationService,
    ): Response|RedirectResponse {
        $email = (string) $request->getSession()->get(self::SESSION_VERIFIED_EMAIL, '');
        $code = (string) $request->getSession()->get(self::SESSION_VERIFIED_CODE, '');

        if ($email === '' || $code === '') {
            return $this->redirectToRoute('app_forgot_password');
        }

        $fieldErrors = [];

        if ($request->isMethod('POST')) {
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('forgot_password_reset', $csrfToken)) {
                $this->addFlash('error', 'Invalid password reset request. Please try again.');

                return $this->redirectToRoute('app_forgot_password_reset');
            }

            $password = (string) $request->request->get('password', '');
            $passwordConfirmation = (string) $request->request->get('password_confirmation', '');

            $passwordError = $validationService->validate([
                'nom' => 'AA',
                'prenom' => 'AA',
                'email' => 'temp@example.com',
                'age' => '18',
                'password' => $password,
            ], true, false)['fieldErrors']['password'] ?? null;

            if ($passwordError !== null) {
                $fieldErrors['password'] = $passwordError;
            }

            if ($passwordConfirmation === '') {
                $fieldErrors['password_confirmation'] = 'Please confirm your new password.';
            } elseif ($password !== $passwordConfirmation) {
                $fieldErrors['password_confirmation'] = 'Passwords do not match.';
            }

            if ($fieldErrors === []) {
                $status = $passwordResetService->resetPassword($email, $code, $password);
                if ($status === PasswordResetService::STATUS_OK) {
                    $request->getSession()->remove(self::SESSION_EMAIL);
                    $request->getSession()->remove(self::SESSION_VERIFIED_EMAIL);
                    $request->getSession()->remove(self::SESSION_VERIFIED_CODE);
                    $this->addFlash('success', 'Your password has been reset. You can now sign in.');

                    return $this->redirectToRoute('app_login');
                }

                $fieldErrors['password'] = match ($status) {
                    PasswordResetService::STATUS_EXPIRED => 'The reset code expired before the password was updated. Request a new one.',
                    PasswordResetService::STATUS_TOO_MANY_ATTEMPTS => 'Too many invalid attempts. Request a new reset code.',
                    PasswordResetService::STATUS_NOT_FOUND => 'No active reset code was found. Request a new one.',
                    default => 'The reset code is invalid.',
                };
            }
        }

        return $this->render('security/forgot_password_reset.html.twig', [
            'email' => $email,
            'fieldErrors' => $fieldErrors,
        ]);
    }
}
