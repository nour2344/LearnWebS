<?php

namespace App\Form;

use App\Entity\Bulletin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BulletinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matiere', TextType::class, [
                'label' => 'Matière',
                'attr' => ['placeholder' => 'Ex : Mathématiques']
            ])
            ->add('note', NumberType::class, [
                'label' => 'Note (/20)',
                'scale' => 2,
                'attr' => ['step' => '0.01', 'min' => 0, 'max' => 20]
            ])
            ->add('date', DateType::class, [
                'label' => 'Date du bulletin',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('semestre', TextType::class, [
                'label' => 'Semestre',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : S1, S2...']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bulletin::class,
        ]);
    }
}
