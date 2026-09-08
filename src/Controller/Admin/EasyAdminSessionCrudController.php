<?php

namespace App\Controller\Admin;

use App\Entity\Session;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EasyAdminSessionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Session::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Sessions')
            ->setSearchFields(['resultat', 'commentaires'])
            ->setDefaultSort(['dateDebut' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('user', 'Utilisateur');
        yield AssociationField::new('exercice', 'Exercice');
        yield DateTimeField::new('dateDebut', 'Début');
        yield DateTimeField::new('dateFin', 'Fin');
        yield IntegerField::new('progress', 'Progression (%)');
        yield IntegerField::new('dureeReelle', 'Durée réelle (s)');
        yield BooleanField::new('terminee', 'Terminée');
        yield TextField::new('Resultat', 'Résultat');
        yield TextField::new('commentaires', 'Commentaires')->hideOnIndex();
    }
}
