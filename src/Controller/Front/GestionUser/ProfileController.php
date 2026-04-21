<?php

namespace App\Controller\Front\GestionUser;

use App\Entity\Utilisateur;
use App\Exception\FaceAuthenticationException;
use App\Service\CompreFaceService;
use App\Service\GestionUser\ProfileIntegrityService;
use App\Service\GestionUser\ValidationService;
use App\Service\TotpService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProfileController extends AbstractController
{
    #[Route('/app/users', name: 'front_gestion_user_index', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidationService $inputValidation,
        ProfileIntegrityService $profileIntegrityService,
        SluggerInterface $slugger,
    ): Response|RedirectResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $formData = [
            'nom' => $currentUser->getNomU(),
            'prenom' => $currentUser->getPrenomU(),
            'email' => $currentUser->getEmailU(),
            'age' => (string) $currentUser->getAgeU(),
            'phone_number' => $currentUser->getPhoneNumber() ?? '',
            'city' => $currentUser->getCity() ?? '',
            'country' => $currentUser->getCountry() ?? '',
            'timezone' => $currentUser->getTimezone() ?? '',
            'occupation' => $currentUser->getOccupation() ?? '',
            'biography' => $currentUser->getBiography() ?? '',
        ];
        $fieldErrors = [];
        $formSubmitted = false;
        $openEdit = false;

        if ($request->isMethod('POST')) {
            $formSubmitted = true;
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('front_profile_edit_' . $currentUser->getIdU(), $csrfToken)) {
                $this->addFlash('error', 'Invalid form token. Please retry.');
                return $this->redirectToRoute('front_gestion_user_index');
            }

            $validated = $inputValidation->validate([
                'nom' => $request->request->get('nom', ''),
                'prenom' => $request->request->get('prenom', ''),
                'email' => $request->request->get('email', ''),
                'age' => $request->request->get('age', ''),
            ], false, false);
            $detailValidation = $inputValidation->validateProfileDetails([
                'phone_number' => $request->request->get('phone_number', ''),
                'city' => $request->request->get('city', ''),
                'country' => $request->request->get('country', ''),
                'timezone' => $request->request->get('timezone', ''),
                'occupation' => $request->request->get('occupation', ''),
                'biography' => $request->request->get('biography', ''),
            ]);

            $formData['nom'] = (string) $validated['data']['nom'];
            $formData['prenom'] = (string) $validated['data']['prenom'];
            $formData['email'] = (string) $validated['data']['email'];
            $formData['age'] = (string) (($validated['data']['age'] ?? '') ?: '');
            $formData['phone_number'] = (string) ($detailValidation['data']['phone_number'] ?? '');
            $formData['city'] = (string) ($detailValidation['data']['city'] ?? '');
            $formData['country'] = (string) ($detailValidation['data']['country'] ?? '');
            $formData['timezone'] = (string) ($detailValidation['data']['timezone'] ?? '');
            $formData['occupation'] = (string) ($detailValidation['data']['occupation'] ?? '');
            $formData['biography'] = (string) ($detailValidation['data']['biography'] ?? '');

            $uploadedAvatar = $request->files->get('profile_picture');
            $avatarError = $this->validateProfilePicture($uploadedAvatar);
            if ($avatarError !== null) {
                $fieldErrors['profile_picture'] = $avatarError;
            }

            if ($validated['errors'] !== [] || $detailValidation['errors'] !== [] || $fieldErrors !== []) {
                $fieldErrors = array_merge($validated['fieldErrors'], $detailValidation['fieldErrors'], $fieldErrors);
                $openEdit = true;
            } else {
                $email = (string) $validated['data']['email'];
                $age = (int) $validated['data']['age'];

                $existing = $entityManager->getRepository(Utilisateur::class)->findOneBy(['emailU' => $email]);
                if ($existing instanceof Utilisateur && $existing->getIdU() !== $currentUser->getIdU()) {
                    $fieldErrors['email'] = 'This email is already used by another account.';
                    $openEdit = true;
                } else {
                    $currentUser->setNomU((string) $validated['data']['nom']);
                    $currentUser->setPrenomU((string) $validated['data']['prenom']);
                    $currentUser->setEmailU($email);
                    $currentUser->setAgeU($age);
                    $currentUser->setPhoneNumber($detailValidation['data']['phone_number']);
                    $currentUser->setCity($detailValidation['data']['city']);
                    $currentUser->setCountry($detailValidation['data']['country']);
                    $currentUser->setTimezone($detailValidation['data']['timezone']);
                    $currentUser->setOccupation($detailValidation['data']['occupation']);
                    $currentUser->setBiography($detailValidation['data']['biography']);

                    if ($uploadedAvatar instanceof UploadedFile) {
                        $currentUser->setProfile_picture_path(
                            $this->storeProfilePicture($uploadedAvatar, $currentUser, $slugger)
                        );
                    }

                    $entityManager->flush();

                    $this->addFlash('success', 'Your profile was updated successfully.');
                    return $this->redirectToRoute('front_gestion_user_index');
                }
            }
        }

        return $this->render('front/gestion_user/profile.html.twig', [
            'currentUser' => $currentUser,
            'form' => $formData,
            'fieldErrors' => $fieldErrors,
            'formSubmitted' => $formSubmitted,
            'openEdit' => $openEdit,
            'integrity' => $profileIntegrityService->buildForUser($currentUser),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    #[Route('/app/users/face-id', name: 'front_gestion_user_face_id_enable', methods: ['POST'])]
    public function enableFaceId(
        Request $request,
        EntityManagerInterface $entityManager,
        CompreFaceService $compreFaceService,
    ): RedirectResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('front_profile_face_id_' . $currentUser->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid Face ID request. Please try again.');
            return $this->redirectToRoute('front_gestion_user_index');
        }

        $faceCapture = (string) $request->request->get('face_capture', '');
        if ($faceCapture === '') {
            $this->addFlash('error', 'Capture your face before saving Face ID.');
            return $this->redirectToRoute('front_gestion_user_index');
        }

        try {
            $faceEnrollment = $compreFaceService->enrollFace(sprintf('mindtrack-user-%d', $currentUser->getIdU()), $faceCapture);
        } catch (FaceAuthenticationException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('front_gestion_user_index');
        }

        $currentUser->setFace_subject((string) $faceEnrollment['subject']);
        $currentUser->setFace_image_id((string) $faceEnrollment['image_id']);
        $currentUser->setFace_enabled(true);
        $entityManager->flush();

        $this->addFlash('success', 'Face ID is now active for your account.');

        return $this->redirectToRoute('front_gestion_user_index');
    }

    #[Route('/app/users/two-factor/setup', name: 'front_gestion_user_totp_setup', methods: ['GET'])]
    public function setupTotp(TotpService $totpService): Response|RedirectResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $secret = $totpService->generateSecret();
        $otpAuthUrl = $totpService->generateOtpAuthUrl($currentUser->getEmailU(), $secret);

        return $this->render('front/gestion_user/two_factor_setup.html.twig', [
            'currentUser' => $currentUser,
            'secret' => $secret,
            'qr_code_url' => $totpService->getQrCodeUrl($otpAuthUrl),
            'seconds_remaining' => $totpService->getSecondsRemaining(),
        ]);
    }

    #[Route('/app/users/two-factor/enable', name: 'front_gestion_user_totp_enable', methods: ['POST'])]
    public function enableTotp(
        Request $request,
        EntityManagerInterface $entityManager,
        TotpService $totpService,
    ): RedirectResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('front_profile_totp_enable_' . $currentUser->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid 2FA request. Please try again.');

            return $this->redirectToRoute('front_gestion_user_totp_setup');
        }

        $secret = trim((string) $request->request->get('secret', ''));
        $code = (string) $request->request->get('code', '');

        if ($secret === '' || !$totpService->verifyCode($secret, $code)) {
            $this->addFlash('error', 'Invalid authenticator code.');

            return $this->redirectToRoute('front_gestion_user_totp_setup');
        }

        $currentUser->setTotp_secret($secret);
        $currentUser->setTotp_enabled(true);
        $entityManager->flush();

        $this->addFlash('success', 'Two-factor authentication is now enabled.');

        return $this->redirectToRoute('front_gestion_user_index');
    }

    #[Route('/app/users/two-factor/disable', name: 'front_gestion_user_totp_disable', methods: ['POST'])]
    public function disableTotp(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('front_profile_totp_disable_' . $currentUser->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid 2FA disable request.');

            return $this->redirectToRoute('front_gestion_user_index');
        }

        $currentUser->setTotp_secret('');
        $currentUser->setTotp_enabled(false);
        $entityManager->flush();

        $this->addFlash('success', 'Two-factor authentication was disabled.');

        return $this->redirectToRoute('front_gestion_user_index');
    }

    #[Route('/app/users/password', name: 'front_gestion_user_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidationService $inputValidation,
        UserPasswordHasherInterface $passwordHasher,
    ): RedirectResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('front_profile_password_' . $currentUser->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid password change request.');

            return $this->redirectToRoute('front_gestion_user_index');
        }

        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword = (string) $request->request->get('new_password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');

        if ($currentPassword === '') {
            $this->addFlash('error', 'Current password is required.');

            return $this->redirectToRoute('front_gestion_user_index');
        }

        if (!$passwordHasher->isPasswordValid($currentUser, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');

            return $this->redirectToRoute('front_gestion_user_index');
        }

        $passwordError = $inputValidation->validate([
            'nom' => 'AA',
            'prenom' => 'AA',
            'email' => 'temp@example.com',
            'age' => '18',
            'password' => $newPassword,
        ], true, false)['fieldErrors']['password'] ?? null;

        if ($passwordError !== null) {
            $this->addFlash('error', $passwordError);

            return $this->redirectToRoute('front_gestion_user_index');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New password and confirmation do not match.');

            return $this->redirectToRoute('front_gestion_user_index');
        }

        if ($currentPassword === $newPassword) {
            $this->addFlash('error', 'Choose a different password from your current one.');

            return $this->redirectToRoute('front_gestion_user_index');
        }

        $currentUser->setMdpsU($passwordHasher->hashPassword($currentUser, $newPassword));
        $entityManager->flush();

        $this->addFlash('success', 'Your password was updated successfully.');

        return $this->redirectToRoute('front_gestion_user_index');
    }

    #[Route('/app/users/delete', name: 'front_gestion_user_delete_account', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage,
    ): RedirectResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('front_delete_account_' . $currentUser->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid delete token.');
            return $this->redirectToRoute('front_gestion_user_index');
        }

        $entityManager->remove($currentUser);
        $entityManager->flush();

        $tokenStorage->setToken(null);
        $session = $request->getSession();
        if ($session !== null) {
            $session->invalidate();
        }

        return $this->redirectToRoute('app_login');
    }

    private function validateProfilePicture(mixed $uploadedAvatar): ?string
    {
        if (!$uploadedAvatar instanceof UploadedFile) {
            return null;
        }

        if (!$uploadedAvatar->isValid()) {
            return 'The uploaded profile picture is invalid.';
        }

        if ($uploadedAvatar->getSize() !== null && $uploadedAvatar->getSize() > 4 * 1024 * 1024) {
            return 'Profile picture must stay under 4 MB.';
        }

        $mimeType = $uploadedAvatar->getMimeType();
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Profile picture must be a JPG, PNG, or WEBP image.';
        }

        return null;
    }

    private function storeProfilePicture(UploadedFile $uploadedAvatar, Utilisateur $user, SluggerInterface $slugger): string
    {
        $uploadDir = (string) $this->getParameter('app.profile_pictures_dir');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $safeName = $slugger->slug((string) ($user->getPrenomU() . '-' . $user->getNomU()))->lower();
        $extension = $uploadedAvatar->guessExtension() ?: 'bin';
        $fileName = sprintf('%s-%d-%s.%s', $safeName, $user->getIdU(), bin2hex(random_bytes(4)), $extension);
        $oldPath = trim((string) $user->getProfile_picture_path());

        $uploadedAvatar->move($uploadDir, $fileName);

        if ($oldPath !== '' && str_starts_with($oldPath, 'uploads/profile-pictures/')) {
            $oldFile = $uploadDir . DIRECTORY_SEPARATOR . basename($oldPath);
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        return 'uploads/profile-pictures/' . $fileName;
    }
}
