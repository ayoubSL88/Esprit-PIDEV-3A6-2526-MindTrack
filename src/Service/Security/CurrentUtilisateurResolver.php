<?php

declare(strict_types=1);

namespace App\Service\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class CurrentUtilisateurResolver
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function resolve(): ?Utilisateur
    {
        $securityUser = $this->security->getUser();

        if ($securityUser instanceof Utilisateur) {
            return $securityUser;
        }

        $session = $this->requestStack->getSession();
        if ($session === null) {
            return null;
        }

        foreach (['current_user_id', 'user_id', 'utilisateur_id', 'id_u'] as $key) {
            $value = $session->get($key);

            if (!is_numeric($value)) {
                continue;
            }

            $user = $this->entityManager->getRepository(Utilisateur::class)->find((int) $value);
            if ($user instanceof Utilisateur) {
                return $user;
            }
        }

        return null;
    }
}
