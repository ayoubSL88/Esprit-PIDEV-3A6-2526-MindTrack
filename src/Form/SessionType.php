<?php
namespace App\Form;

use App\Entity\Session;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('resultat', TextType::class, [
                'label' => 'Résultat',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('commentaires', TextareaType::class, [
                'label' => 'Commentaires personnels',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('ressenti', IntegerType::class, [
                'label' => 'Ressenti (1-5)',
                'required' => false,
                'constraints' => [new Range(min: 1, max: 5)],
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 5]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Session::class,
        ]);
    }
}