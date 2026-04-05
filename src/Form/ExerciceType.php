<?php

namespace App\Form;

use App\Entity\Exercice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class ExerciceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ⚠️ On enlève idEx ! L'ID est auto-généré par la BDD
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'attr' => ['class' => 'form-control']
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
            ->add('difficulte', ChoiceType::class, [
                'label' => 'Difficulté',
                'choices' => [
                    'Facile' => 'FACILE',
                    'Moyen' => 'MOYEN',
                    'Difficile' => 'DIFFICILE',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('demarche', TextareaType::class, [
                'label' => 'Démarche',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
        ;   
            // Ajouter la date de création seulement si c'est une édition
        if ($options['is_edit'] === true) {
            $builder->add('date_creation', DateTimeType::class, [
                'widget' => 'single_text',
                'disabled' => true,
                'required' => false,
                'label' => 'Date de création',
                'attr' => [
                    'readonly' => true,
                    'class' => 'bg-light'
                ]
            ]);
            
            $builder->add('date_modification', DateTimeType::class, [
                'widget' => 'single_text',
                'disabled' => true,
                'required' => false,
                'label' => 'Date de modification',
                'attr' => [
                    'readonly' => true,
                    'class' => 'bg-light'
                ]
            ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercice::class,
            'is_edit' => false, // Option personnalisée pour différencier création et édition
        ]);
    }
}