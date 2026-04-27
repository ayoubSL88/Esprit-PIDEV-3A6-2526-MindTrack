<?php

namespace App\Form;

use App\Entity\Journalemotionnel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\UX\Dropzone\Form\DropzoneType;

class JournalemotionnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notePersonnelle', TextareaType::class, [
                'label' => 'Note personnelle',
                'required' => true,
                'help' => 'Ecrivez une note riche entre 5 et 5000 caracteres utiles.',
                'sanitize_html' => true,
                'sanitizer' => 'journal_note',
                'attr' => [
                    'rows' => 12,
                    'class' => 'tinymce',
                    'data-theme' => 'journal',
                ],
            ])
            ->add('screenshotFile', DropzoneType::class, [
                'label' => 'Screenshot',
                'mapped' => false,
                'required' => false,
                'help' => 'Ajoutez une capture d ecran pour accompagner votre note.',
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                    'placeholder' => 'Drop a screenshot or browse',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '8M',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Ajoutez une image PNG, JPG ou WEBP.',
                    ]),
                ],
            ])
            ->add('removeScreenshot', CheckboxType::class, [
                'label' => 'Remove current screenshot',
                'mapped' => false,
                'required' => false,
            ])
            ->add('audioFile', DropzoneType::class, [
                'label' => 'Audio note',
                'mapped' => false,
                'required' => false,
                'help' => 'Ajoutez un message vocal au format MP3, WAV, OGG, WEBM ou M4A.',
                'attr' => [
                    'accept' => 'audio/mpeg,audio/wav,audio/x-wav,audio/ogg,audio/webm,audio/mp4,audio/aac,audio/x-m4a',
                    'placeholder' => 'Drop an audio file or browse',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '15M',
                        'mimeTypes' => [
                            'audio/mpeg',
                            'audio/wav',
                            'audio/x-wav',
                            'audio/ogg',
                            'audio/webm',
                            'audio/mp4',
                            'audio/aac',
                            'audio/x-m4a',
                        ],
                        'mimeTypesMessage' => 'Ajoutez un audio MP3, WAV, OGG, WEBM ou M4A.',
                    ]),
                ],
            ])
            ->add('removeAudio', CheckboxType::class, [
                'label' => 'Remove current audio note',
                'mapped' => false,
                'required' => false,
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
