<?php

namespace App\Form\Admin\GestionSuiviHabitudes;

use App\Entity\Habitude;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HabitudeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('frequence', ChoiceType::class, [
                'label' => 'Frequence',
                'choices' => [
                    'Quotidien' => 'QUOTIDIEN',
                    'Hebdomadaire' => 'HEBDOMADAIRE',
                    'Mensuel' => 'MENSUEL',
                ],
            ])
            ->add('objectif', TextType::class, ['label' => 'Objectif'])
            ->add('habitType', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Booleen' => 'BOOLEAN',
                    'Numerique' => 'NUMERIC',
                ],
                'attr' => [
                    'data-habit-type-field' => '1',
                ],
            ])
            ->add('targetValue', IntegerType::class, [
                'label' => 'Valeur cible',
                'help' => 'Si le type est BOOLEAN, choisissez True ou False. Si le type est NUMERIC, saisissez un nombre.',
                'attr' => [
                    'data-target-value-field' => '1',
                    'min' => 0,
                    'step' => 1,
                    'inputmode' => 'numeric',
                ],
            ])
            ->add('unit', TextType::class, [
                'label' => 'Unite',
                'required' => false,
                'attr' => [
                    'data-unit-field' => '1',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Habitude::class,
        ]);
    }
}
