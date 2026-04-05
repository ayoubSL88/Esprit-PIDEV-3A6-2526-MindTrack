<?php

namespace App\Controller\Admin\GestionUser;

use App\Entity\Utilisateur;
use App\Service\UserInputValidationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class UserManagementController extends AbstractController
{
    #[Route('/admin/users/create', name: 'admin_gestion_user_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        Connection $connection,
        UserPasswordHasherInterface $passwordHasher,
        UserInputValidationService $inputValidation,
    ): Response|RedirectResponse {
        $formData = [
            'nom' => '',
            'prenom' => '',
            'email' => '',
            'age' => '',
            'role' => 'USER',
        ];
        $fieldErrors = [];
        $formSubmitted = false;

        if ($request->isMethod('POST')) {
            $formSubmitted = true;
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('create_user', $csrfToken)) {
                $this->addFlash('error', 'Invalid form token. Please retry.');
                return $this->redirectToRoute('admin_gestion_user_create');
            }

            $validated = $inputValidation->validate([
                'nom' => $request->request->get('nom', ''),
                'prenom' => $request->request->get('prenom', ''),
                'email' => $request->request->get('email', ''),
                'age' => $request->request->get('age', ''),
                'role' => $request->request->get('role', 'USER'),
                'password' => $request->request->get('password', ''),
            ], true, true);

            $formData['nom'] = (string) $validated['data']['nom'];
            $formData['prenom'] = (string) $validated['data']['prenom'];
            $formData['email'] = (string) $validated['data']['email'];
            $formData['age'] = (string) (($validated['data']['age'] ?? '') ?: '');
            $formData['role'] = (string) $validated['data']['role'];

            if ($validated['errors'] !== []) {
                $fieldErrors = $validated['fieldErrors'];
            } elseif ($utilisateurRepository->findOneBy(['emailU' => $formData['email']]) instanceof Utilisateur) {
                $fieldErrors['email'] = 'This email is already used by another account.';
            } else {
                $age = (int) $validated['data']['age'];
                $plainPassword = (string) $validated['data']['password'];
                $nextUserId = (int) $connection->fetchOne('SELECT COALESCE(MAX(id_u), 0) + 1 FROM utilisateur');

                $user = new Utilisateur();
                $user->setIdU($nextUserId);
                $user->setNomU($formData['nom']);
                $user->setPrenomU($formData['prenom']);
                $user->setEmailU($formData['email']);
                $user->setAgeU($age);
                $user->setRoleU($formData['role']);
                $user->setFace_subject('');
                $user->setFace_image_id('');
                $user->setFace_enabled(false);
                $user->setProfile_picture_path('');
                $user->setTotp_secret('');
                $user->setTotp_enabled(false);
                $user->setMdpsU($passwordHasher->hashPassword($user, $plainPassword));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'User created successfully.');
                return $this->redirectToRoute('admin_gestion_user_index');
            }
        }

        return $this->render('admin/gestion_user/create_user.html.twig', [
            'form' => $formData,
            'fieldErrors' => $fieldErrors,
            'formSubmitted' => $formSubmitted,
        ]);
    }

    #[Route('/admin/users', name: 'admin_gestion_user_index')]
    public function list(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $role = strtoupper(trim((string) $request->query->get('role', '')));

        if ($role !== 'ADMIN' && $role !== 'USER') {
            $role = '';
        }

        $users = $utilisateurRepository->findForAdminList($search, $role !== '' ? $role : null);

        return $this->render('admin/gestion_user/user_management.html.twig', [
            'users' => $users,
            'filters' => [
                'q' => $search,
                'role' => $role,
            ],
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_gestion_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        UserInputValidationService $inputValidation,
    ): Response|RedirectResponse
    {
        $user = $utilisateurRepository->find($id);
        if (!$user instanceof Utilisateur) {
            throw new NotFoundHttpException('User not found.');
        }

        $formData = [
            'nom' => $user->getNomU(),
            'prenom' => $user->getPrenomU(),
            'email' => $user->getEmailU(),
            'age' => (string) $user->getAgeU(),
        ];
        $fieldErrors = [];
        $formSubmitted = false;

        if ($request->isMethod('POST')) {
            $formSubmitted = true;
            $csrfToken = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('edit_user_' . $user->getIdU(), $csrfToken)) {
                $this->addFlash('error', 'Invalid form token. Please retry.');
                return $this->redirectToRoute('admin_gestion_user_edit', ['id' => $user->getIdU()]);
            }

            $validated = $inputValidation->validate([
                'nom' => $request->request->get('nom', ''),
                'prenom' => $request->request->get('prenom', ''),
                'email' => $request->request->get('email', ''),
                'age' => $request->request->get('age', ''),
            ], false, false);

            $formData['nom'] = (string) $validated['data']['nom'];
            $formData['prenom'] = (string) $validated['data']['prenom'];
            $formData['email'] = (string) $validated['data']['email'];
            $formData['age'] = (string) (($validated['data']['age'] ?? '') ?: '');

            if ($validated['errors'] !== []) {
                $fieldErrors = $validated['fieldErrors'];
            } else {
                $email = (string) $validated['data']['email'];
                $age = (int) $validated['data']['age'];

                $existing = $utilisateurRepository->findOneBy(['emailU' => $email]);
                if ($existing instanceof Utilisateur && $existing->getIdU() !== $user->getIdU()) {
                    $fieldErrors['email'] = 'This email is already used by another account.';
                } else {
                    $user->setNomU((string) $validated['data']['nom']);
                    $user->setPrenomU((string) $validated['data']['prenom']);
                    $user->setEmailU($email);
                    $user->setAgeU($age);

                    $entityManager->flush();
                    $this->addFlash('success', 'User updated successfully.');

                    return $this->redirectToRoute('admin_gestion_user_index');
                }
            }
        }

        return $this->render('admin/gestion_user/edit_user.html.twig', [
            'targetUser' => $user,
            'form' => $formData,
            'fieldErrors' => $fieldErrors,
            'formSubmitted' => $formSubmitted,
        ]);
    }

    #[Route('/admin/users/{id}/role', name: 'admin_gestion_user_change_role', methods: ['POST'])]
    public function changeRole(int $id, Request $request, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $utilisateurRepository->find($id);
        if (!$user instanceof Utilisateur) {
            throw new NotFoundHttpException('User not found.');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('role_change_' . $user->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid role-change token.');
            return $this->redirectToRoute('admin_gestion_user_index');
        }

        $role = strtoupper(trim((string) $request->request->get('role', '')));
        if ($role !== 'ADMIN' && $role !== 'USER') {
            $this->addFlash('error', 'Invalid role value.');
            return $this->redirectToRoute('admin_gestion_user_index');
        }

        $loggedUser = $this->getUser();
        if ($loggedUser instanceof Utilisateur && $loggedUser->getIdU() === $user->getIdU() && $role !== 'ADMIN') {
            $this->addFlash('error', 'You cannot remove your own admin role while logged in.');
            return $this->redirectToRoute('admin_gestion_user_index');
        }

        $user->setRoleU($role);
        $entityManager->flush();

        $this->addFlash('success', 'User role updated.');

        return $this->redirectToRoute('admin_gestion_user_index');
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_gestion_user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, UtilisateurRepository $utilisateurRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $utilisateurRepository->find($id);
        if (!$user instanceof Utilisateur) {
            throw new NotFoundHttpException('User not found.');
        }

        $csrfToken = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getIdU(), $csrfToken)) {
            $this->addFlash('error', 'Invalid delete token.');
            return $this->redirectToRoute('admin_gestion_user_index');
        }

        $loggedUser = $this->getUser();
        if ($loggedUser instanceof Utilisateur && $loggedUser->getIdU() === $user->getIdU()) {
            $this->addFlash('error', 'You cannot delete your own account while logged in.');
            return $this->redirectToRoute('admin_gestion_user_index');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'User deleted successfully.');

        return $this->redirectToRoute('admin_gestion_user_index');
    }
}