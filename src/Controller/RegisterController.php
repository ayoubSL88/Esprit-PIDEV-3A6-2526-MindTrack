<?php

namespace App\Controller;

use App\Entity\Utilisateur;
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

        if ($request->isMethod('POST')) {
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                $this->addFlash('error', 'Invalid form token. Please try again.');
                return $this->redirectToRoute('app_register');
            }

            $formData['nom'] = trim((string) $request->request->get('nom', ''));
            $formData['prenom'] = trim((string) $request->request->get('prenom', ''));
            $formData['email'] = strtolower(trim((string) $request->request->get('email', '')));
            $formData['age'] = trim((string) $request->request->get('age', ''));
            $plainPassword = (string) $request->request->get('password', '');

            if ($formData['nom'] === '' || $formData['prenom'] === '' || $formData['email'] === '' || $formData['age'] === '' || $plainPassword === '') {
                $this->addFlash('error', 'All fields are required.');
                return $this->render('security/register.html.twig', ['form' => $formData]);
            }

            if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Please provide a valid email address.');
                return $this->render('security/register.html.twig', ['form' => $formData]);
            }

            $age = (int) $formData['age'];
            if ($age < 10 || $age > 120) {
                $this->addFlash('error', 'Please provide a valid age.');
                return $this->render('security/register.html.twig', ['form' => $formData]);
            }

            if (mb_strlen($plainPassword) < 6) {
                $this->addFlash('error', 'Password must contain at least 6 characters.');
                return $this->render('security/register.html.twig', ['form' => $formData]);
            }

            $existing = $entityManager->getRepository(Utilisateur::class)->findOneBy(['emailU' => $formData['email']]);
            if ($existing !== null) {
                $this->addFlash('error', 'This email is already used.');
                return $this->render('security/register.html.twig', ['form' => $formData]);
            }

            $user = new Utilisateur();
            $nextUserId = (int) $connection->fetchOne('SELECT COALESCE(MAX(id_u), 0) + 1 FROM utilisateur');

            $user->setIdU($nextUserId);
            $user->setNomU($formData['nom']);
            $user->setPrenomU($formData['prenom']);
            $user->setEmailU($formData['email']);
            $user->setAgeU($age);
            $user->setRoleU('USER');
            $user->setFace_subject('');
            $user->setFace_image_id('');
            $user->setFace_enabled(false);
            $user->setProfile_picture_path('');
            $user->setTotp_secret('');
            $user->setTotp_enabled(false);
            $user->setMdpsU($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Account created successfully. You can now sign in.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', ['form' => $formData]);
    }
}
