<?php

namespace App\Controller\Front\GestionUser;

use App\Entity\Utilisateur;
use App\Service\UserInputValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class ProfileController extends AbstractController
{
    #[Route('/app/users', name: 'front_gestion_user_index', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        EntityManagerInterface $entityManager,
        UserInputValidationService $inputValidation,
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

            $formData['nom'] = (string) $validated['data']['nom'];
            $formData['prenom'] = (string) $validated['data']['prenom'];
            $formData['email'] = (string) $validated['data']['email'];
            $formData['age'] = (string) (($validated['data']['age'] ?? '') ?: '');

            if ($validated['errors'] !== []) {
                $fieldErrors = $validated['fieldErrors'];
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
        ]);
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
}