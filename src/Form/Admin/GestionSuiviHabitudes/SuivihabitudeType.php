<?php

namespace App\Form\Admin\GestionSuiviHabitudes;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
use App\Entity\Utilisateur;
use App\Repository\HabitudeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuivihabitudeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur|null $currentUser */
        $currentUser = $options['current_user'];

        $builder
            ->add('idHabitude', EntityType::class, [
                'class' => Habitude::class,
                'choice_label' => 'nom',
                'label' => 'Habitude',
                'placeholder' => 'Choisir une habitude',
                'query_builder' => static function (HabitudeRepository $repository) use ($currentUser) {
                    $qb = $repository->createQueryBuilder('h')->orderBy('h.nom', 'ASC');

                    if ($currentUser instanceof Utilisateur) {
                        $qb
                            ->andWhere('h.idU = :owner')
                            ->setParameter('owner', $currentUser);
                    }

                    return $qb;
                },
            ])
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('etat', CheckboxType::class, [
                'label' => 'Completee',
                'required' => false,
            ])
            ->add('valeur', IntegerType::class, ['label' => 'Valeur']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Suivihabitude::class,
            'current_user' => null,
        ]);

        $resolver->setAllowedTypes('current_user', ['null', Utilisateur::class]);
    }
}
