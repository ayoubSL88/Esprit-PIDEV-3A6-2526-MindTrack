<?php

namespace App\Form;

use App\Entity\Humeur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HumeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date',
                'required' => false,
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'invalid_message' => 'Enter a valid date using the YYYY-MM-DD format.',
                'attr' => [
                    'placeholder' => 'YYYY-MM-DD',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('typeHumeur', ChoiceType::class, [
                'label' => 'Mood type',
                'placeholder' => 'Choose a mood',
                'empty_data' => '',
                'choices' => [
                    'Sad' => 'sad',
                    'Stressed' => 'anxious',
                    'Tired' => 'tired',
                    'Happy' => 'happy',
                    'Neutral' => 'neutural',
                ],
                'required' => false,
            ])
            ->add('intensite', TextType::class, [
                'label' => 'Intensity',
                'help' => 'Enter a whole number between 1 and 10.',
                'required' => false,
                'invalid_message' => 'Enter a valid whole number for intensity.',
                'attr' => [
                    'placeholder' => '1 to 10',
                    'inputmode' => 'numeric',
                    'autocomplete' => 'off',
                ],
            ]);

        $builder->get('intensite')->addModelTransformer(new CallbackTransformer(
            static fn (?int $value): string => $value === null ? '' : (string) $value,
            static function (mixed $value): ?int {
                $normalized = trim((string) ($value ?? ''));

                if ($normalized === '') {
                    return null;
                }

                if (filter_var($normalized, FILTER_VALIDATE_INT) === false) {
                    throw new TransformationFailedException('Enter a valid whole number for intensity.');
                }

                return (int) $normalized;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Humeur::class,
        ]);
    }
}
