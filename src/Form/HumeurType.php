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
            ])
            ->add('typeHumeur', ChoiceType::class, [
                'label' => 'Type humeur',
                'choices' => [
                    'Sad' => 'sad',
                    'Anxious' => 'anxious',
                    'Happy' => 'happy',
                    'Neutural' => 'neutural',
                ],
            ])
            ->add('intensite', IntegerType::class, [
                'label' => 'Intensite',
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
