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
use Symfony\Component\Validator\Constraints as Assert;

class ExerciceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'exercice :',
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Ex: Méditation respiratoire'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom doit contenir au moins 3 caractères et ne peut pas dépasser 50 caractères']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9\s\-\/\(\)\:\&\’\'\éèêëàâäôöùûüç]+$/u',
                        'message' => 'Le nom contient des caractères non autorisés'
                    ])
                ]
            ])
            ->add('type', TextType::class, [
                'label' => 'Type :',
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Ex: Méditation, Respiration, Visualisation'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type doit contenir au moins 2 caractères']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le type doit contenir au moins {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée :',
                'attr' => [
                    'class' => 'form-control', 
                    'min' => 1,
                    'max' => 90
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La durée est obligatoire']),
                    new Assert\Positive(['message' => 'La durée doit être supérieure à 1 minute et est un nombre positif']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 90,
                        'notInRangeMessage' => 'La durée doit être comprise entre {{ min }} et {{ max }} minutes'
                    ])
                ]
            ])
            ->add('difficulte', ChoiceType::class, [
                'label' => 'Difficulté :',
                'choices' => [
                    'Facile' => 'FACILE',
                    'Moyen' => 'MOYEN',
                    'Difficile' => 'DIFFICILE',
                ],
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La difficulté est obligatoire']),
                    new Assert\Choice([
                        'choices' => ['FACILE', 'MOYEN', 'DIFFICILE'],
                        'message' => 'Veuillez choisir une difficulté valide'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description :',
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description doit contenir au moins 10 caractères et ne peut pas dépasser 500 caractères']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 500,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('demarche', TextareaType::class, [
                'label' => 'Démarche :',
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La démarche doit contenir au moins 20 caractères et ne peut pas dépasser 1000 caractères']),
                    new Assert\Length([
                        'min' => 20,
                        'max' => 1000,
                        'minMessage' => 'La démarche doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La démarche ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
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
            'is_edit' => false,
        ]);
    }
}