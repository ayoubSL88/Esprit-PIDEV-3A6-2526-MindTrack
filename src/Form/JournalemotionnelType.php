<?php

namespace App\Form;

use App\Entity\Journalemotionnel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JournalemotionnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notePersonnelle', TextareaType::class, [
                'label' => 'Note personnelle',
                'required' => true,
                'help' => 'Ecrivez entre 5 et 255 caracteres.',
                'attr' => [
                    'rows' => 6,
                    'maxlength' => 255,
                ],
            ])
            ->add('dateCreation', DateTimeType::class, [
                'label' => 'Date creation',
                'widget' => 'single_text',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journalemotionnel::class,
        ]);
    }
}
