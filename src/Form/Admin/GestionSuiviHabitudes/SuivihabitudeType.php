<?php

namespace App\Form\Admin\GestionSuiviHabitudes;

use App\Entity\Habitude;
use App\Entity\Suivihabitude;
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
        $builder
            ->add('idHabitude', EntityType::class, [
                'class' => Habitude::class,
                'choice_label' => 'nom',
                'label' => 'Habitude',
                'placeholder' => 'Choisir une habitude',
                'query_builder' => static fn (HabitudeRepository $repository) => $repository->createQueryBuilder('h')->orderBy('h.nom', 'ASC'),
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
        ]);
    }
}
