<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Exception\FaceAuthenticationException;
// use App\Service\CompreFaceService;
use App\Service\GestionUser\ValidationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        Connection $connection,
        UserPasswordHasherInterface $passwordHasher,
        ValidationService $inputValidation,
        // CompreFaceService $compreFaceService,
    ): Response|RedirectResponse {
        if ($this->getUser() !== null) {
            return $this->isGranted('ROLE_ADMIN')
                ? $this->redirectToRoute('admin_dashboard')
                : $this->redirectToRoute('front_home');
        }

        $formData = [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'age' => '',
        ];
        $fieldErrors = [];
        $formSubmitted = false;
        $faceEnabled = false;

        if ($request->isMethod('POST')) {
            $formSubmitted = true;
            $faceEnabled = $request->request->getBoolean('enable_face_id');
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                $this->addFlash('error', 'Invalid form token. Please try again.');
                return $this->redirectToRoute('app_register');
            }

            $validated = $inputValidation->validate([
                'nom' => $request->request->get('nom', ''),
                'prenom' => $request->request->get('prenom', ''),
                'email' => $request->request->get('email', ''),
                'age' => $request->request->get('age', ''),
                'password' => $request->request->get('password', ''),
            ], true, false);

            $formData['nom'] = (string) $validated['data']['nom'];
            $formData['prenom'] = (string) $validated['data']['prenom'];
            $formData['email'] = (string) $validated['data']['email'];
            $formData['age'] = (string) (($validated['data']['age'] ?? '') ?: '');

            if ($validated['errors'] !== []) {
                $fieldErrors = $validated['fieldErrors'];

                return $this->render('security/register.html.twig', [
                    'form' => $formData,
                    'fieldErrors' => $fieldErrors,
                    'formSubmitted' => $formSubmitted,
                ]);
            }

            $plainPassword = (string) $validated['data']['password'];
            $age = (int) $validated['data']['age'];
            $faceCapture = (string) $request->request->get('face_capture', '');

            $existing = $entityManager->getRepository(Utilisateur::class)->findOneBy(['emailU' => $formData['email']]);
            if ($existing !== null) {
                $fieldErrors['email'] = 'This email is already used.';
                return $this->render('security/register.html.twig', [
                    'form' => $formData,
                    'fieldErrors' => $fieldErrors,
                    'formSubmitted' => $formSubmitted,
                    'faceEnabled' => $faceEnabled,
                ]);
            }

            $nextUserId = (int) $connection->fetchOne('SELECT COALESCE(MAX(id_u), 0) + 1 FROM utilisateur');
            $faceEnrollment = null;

            // SECTION FACE ID COMPLÈTEMENT DÉSACTIVÉE
            if ($faceEnabled) {
                // Désactiver forcément Face ID
                $faceEnabled = false;
                
                // Version commentée du code original
                /*
                if ($faceCapture === '') {
                    $fieldErrors['face_capture'] = 'Capture your face before enabling Face ID.';

                    return $this->render('security/register.html.twig', [
                        'form' => $formData,
                        'fieldErrors' => $fieldErrors,
                        'formSubmitted' => $formSubmitted,
                        'faceEnabled' => $faceEnabled,
                    ]);
                }

                try {
                    $faceEnrollment = $compreFaceService->enrollFace(sprintf('mindtrack-user-%d', $nextUserId), $faceCapture);
                } catch (FaceAuthenticationException $exception) {
                    $fieldErrors['face_capture'] = $exception->getMessage();

                    return $this->render('security/register.html.twig', [
                        'form' => $formData,
                        'fieldErrors' => $fieldErrors,
                        'formSubmitted' => $formSubmitted,
                        'faceEnabled' => $faceEnabled,
                    ]);
                }
                */
            }

            $user = new Utilisateur();

            $user->setIdU($nextUserId);
            $user->setNomU($formData['nom']);
            $user->setPrenomU($formData['prenom']);
            $user->setEmailU($formData['email']);
            $user->setAgeU($age);
            $user->setRoleU('USER');
            $user->setFace_subject('');  // Forcé à vide
            $user->setFace_image_id('');  // Forcé à vide
            $user->setFace_enabled(false);  // Forcé à false
            $user->setProfile_picture_path('');
            $user->setTotp_secret('');
            $user->setTotp_enabled(false);
            $user->setMdpsU($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Account created successfully. You can now sign in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $formData,
            'fieldErrors' => $fieldErrors,
            'formSubmitted' => $formSubmitted,
            'faceEnabled' => $faceEnabled,
        ]);
    }
}