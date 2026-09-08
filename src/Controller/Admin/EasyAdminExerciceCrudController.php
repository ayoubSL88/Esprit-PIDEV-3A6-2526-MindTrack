<?php

namespace App\Controller\Admin;

use App\Entity\Exercice;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EasyAdminExerciceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Exercice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Exercices')
            ->setSearchFields(['nom', 'type', 'difficulte'])
            ->setDefaultSort(['date_creation' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('nom', 'Nom');
        yield TextField::new('type', 'Type');
        yield IntegerField::new('duree', 'Durée (min)');
        yield ChoiceField::new('difficulte', 'Difficulté')
            ->setChoices(['Facile' => 'FACILE', 'Moyen' => 'MOYEN', 'Difficile' => 'DIFFICILE']);
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield TextareaField::new('demarche', 'Démarche')->hideOnIndex();
        yield DateTimeField::new('date_creation', 'Créé le')->hideOnForm();
        yield DateTimeField::new('date_modification', 'Modifié le')->hideOnForm();
    }
}
