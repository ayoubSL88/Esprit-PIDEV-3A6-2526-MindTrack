<?php

namespace App\Form;

use App\Entity\Objectif;
use App\Entity\Planaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PlanactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('idObj', EntityType::class, [
                'label' => 'Objectif lié',
                'class' => Objectif::class,
                'choice_label' => 'titre',
                'placeholder' => 'Choisir un objectif',
                'constraints' => [
                    new NotBlank(message: 'L’objectif lié est obligatoire.'),
                ],
            ])
            ->add('etape', TextType::class, [
                'label' => 'Etape',
                'constraints' => [
                    new NotBlank(message: 'L’étape est obligatoire.'),
                    new Length(min: 3, max: 255, minMessage: 'L’étape doit contenir au moins {{ limit }} caractères.', maxMessage: 'L’étape ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('priorite', IntegerType::class, [
                'label' => 'Priorité (1 à 10)',
                'constraints' => [
                    new NotBlank(message: 'La priorité est obligatoire.'),
                    new Range(
                        min: 1,
                        max: 10,
                        notInRangeMessage: 'La priorité doit être comprise entre {{ min }} et {{ max }}.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planaction::class,
        ]);
    }
}
