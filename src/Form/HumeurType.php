<?php

namespace App\Form;

use App\Entity\Humeur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HumeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'required' => true,
            ])
            ->add('typeHumeur', ChoiceType::class, [
                'label' => 'Type humeur',
                'placeholder' => 'Choisir une humeur',
                'choices' => [
                    'Sad' => 'sad',
                    'Anxious' => 'anxious',
                    'Happy' => 'happy',
                    'Neutural' => 'neutural',
                ],
                'required' => true,
            ])
            ->add('intensite', IntegerType::class, [
                'label' => 'Intensite',
                'help' => 'Entrez une valeur entre 1 et 10.',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'max' => 10,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Humeur::class,
        ]);
    }
}
