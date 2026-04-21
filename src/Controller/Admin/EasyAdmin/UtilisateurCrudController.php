<?php

namespace App\Controller\Admin\EasyAdmin;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class UtilisateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setDefaultSort(['idU' => 'DESC'])
            ->setSearchFields(['idU', 'nomU', 'prenomU', 'emailU', 'roleU']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('idU', 'ID')->hideOnForm();
        yield TextField::new('nomU', 'Last Name');
        yield TextField::new('prenomU', 'First Name');
        yield EmailField::new('emailU', 'Email');
        yield IntegerField::new('ageU', 'Age');
        yield ChoiceField::new('roleU', 'Role')
            ->setChoices([
                'Admin' => 'ADMIN',
                'User' => 'USER',
            ]);
        yield BooleanField::new('face_enabled', 'Face Enabled');
        yield BooleanField::new('totp_enabled', '2FA Enabled');

        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('profile_picture_path', 'Profile Picture Path');
            yield TextField::new('face_subject', 'Face Subject')->hideOnIndex();
            yield TextField::new('face_image_id', 'Face Image ID')->hideOnIndex();
        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            parent::deleteEntity($entityManager, $entityInstance);

            return;
        }

        $loggedUser = $this->getUser();
        if ($loggedUser instanceof Utilisateur && $loggedUser->getIdU() === $entityInstance->getIdU()) {
            $this->addFlash('danger', 'You cannot delete your own account.');

            return;
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            parent::updateEntity($entityManager, $entityInstance);

            return;
        }

        $loggedUser = $this->getUser();
        if (
            $loggedUser instanceof Utilisateur
            && $loggedUser->getIdU() === $entityInstance->getIdU()
            && strtoupper((string) $entityInstance->getRoleU()) !== 'ADMIN'
        ) {
            $this->addFlash('danger', 'You cannot remove your own admin role.');
            $entityManager->refresh($entityInstance);

            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
