<?php

namespace App\Form;

use App\Entity\Depense;
use App\Enum\CategorieDepense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class DepenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => CategorieDepense::cases(),
                'choice_label' => fn($choice) => $choice->value,
                'placeholder' => 'Sélectionnez une catégorie',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'La catégorie est obligatoire.']),
                ],
            ])
            ->add('montantD', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être supérieur à zéro.']),
                ],
            ])
            ->add('dateD', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date ne peut pas être dans le futur.',
                    ]),
                ],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Depense::class,
        ]);
    }
}
