<?php

namespace App\Form;

use App\Entity\Recette;
use App\Enum\SourceRecette;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RecetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('source', ChoiceType::class, [
                'label' => 'Source',
                'choices' => SourceRecette::cases(),
                'choice_label' => fn(SourceRecette $source) => $source->value,
                'placeholder' => 'Choisissez une source',
                'constraints' => [
                    new Assert\NotNull(['message' => 'La source est obligatoire.']),
                ],
            ])
            ->add('montantR', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être supérieur à zéro.']),
                ],
            ])
            ->add('dateR', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date est obligatoire.']),
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date ne peut pas être dans le futur.',
                    ]),
                ],
            ])
            ->add('noteR', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'La note ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recette::class,
        ]);
    }
}
