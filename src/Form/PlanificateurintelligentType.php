<?php

namespace App\Form;

use App\Entity\Objectif;
use App\Entity\Planificateurintelligent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PlanificateurintelligentType extends AbstractType
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
            ->add('modeOrganisation', ChoiceType::class, [
                'label' => 'Mode d’organisation',
                'choices' => [
                    '🔄 Flexible (Adaptatif)' => 'flexible',
                    '⚖️ Équilibré (Équilibre parfait)' => 'equilibre',
                    '⚡ Intensif (Maximise l\'efficacité)' => 'intensif',
                ],
                'placeholder' => 'Choisir un mode',
                'constraints' => [
                    new NotBlank(message: 'Le mode d’organisation est obligatoire.'),
                    new Length(max: 30, maxMessage: 'Le mode d’organisation ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('capaciteQuotidienne', IntegerType::class, [
                'label' => 'Capacité quotidienne (heures)',
                'attr' => ['min' => 1, 'max' => 24],
                'constraints' => [
                    new NotBlank(message: 'La capacité quotidienne est obligatoire.'),
                    new Range(
                        min: 1,
                        max: 24,
                        notInRangeMessage: 'La capacité quotidienne doit être comprise entre {{ min }} et {{ max }}.'
                    ),
                ],
            ])
            ->add('derniereGeneration', DateTimeType::class, [
                'label' => 'Dernière génération',
                'widget' => 'single_text',
                'html5' => true,
                'invalid_message' => 'Veuillez saisir une date et une heure valides.',
            ])
            ->add('genererIA', CheckboxType::class, [
                'label' => '🤖 Générer automatiquement le mode, la capacité et les conseils avec IA',
                'required' => false,
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planificateurintelligent::class,
        ]);
    }
}