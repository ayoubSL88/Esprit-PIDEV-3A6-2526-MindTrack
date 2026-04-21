<?php

namespace App\Form;

use App\Entity\Jalonprogression;
use App\Entity\Objectif;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class JalonprogressionType extends AbstractType
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
            ->add('titre', TextType::class, [
                'label' => 'Titre du jalon',
                'constraints' => [
                    new NotBlank(message: 'Le titre du jalon est obligatoire.'),
                    new Length(min: 3, max: 150, minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('dateCible', DateType::class, [
                'label' => 'Date cible',
                'widget' => 'single_text',
                'html5' => true,
                'invalid_message' => 'Veuillez saisir une date cible valide.',
            ])
            ->add('atteint', CheckboxType::class, [
                'label' => 'Jalon atteint',
                'required' => false,
            ])
            ->add('dateAtteinte', DateType::class, [
                'label' => 'Date atteinte',
                'widget' => 'single_text',
                'html5' => true,
                'invalid_message' => 'Veuillez saisir une date atteinte valide.',
            ])
            ->add('pourcentageProgression', IntegerType::class, [
                'label' => 'Progression (%)',
                'constraints' => [
                    new NotBlank(message: 'Le pourcentage de progression est obligatoire.'),
                    new Range(
                        min: 0,
                        max: 100,
                        notInRangeMessage: 'Le pourcentage doit être compris entre {{ min }} et {{ max }}.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jalonprogression::class,
        ]);
    }
}
