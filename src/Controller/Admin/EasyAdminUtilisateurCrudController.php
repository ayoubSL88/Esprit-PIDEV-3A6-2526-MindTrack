<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EasyAdminUtilisateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Utilisateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un utilisateur')
            ->setSearchFields(['nomU', 'prenomU', 'emailU']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nomU', 'Nom');
        yield TextField::new('prenomU', 'Prénom');
        yield EmailField::new('emailU', 'Email');
        yield IntegerField::new('ageU', 'Âge');
        yield ChoiceField::new('roleU', 'Rôle')
            ->setChoices(['Utilisateur' => 'USER', 'Administrateur' => 'ADMIN']);
    }
}
