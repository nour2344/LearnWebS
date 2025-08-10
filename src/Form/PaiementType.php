<?php

namespace App\Form;

use App\Entity\Paiement;
use App\Entity\Etudiant;
use App\Enum\TypePaiement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints as Assert;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typePaiement', ChoiceType::class, [
                'label' => 'Type de paiement',
                'choices' => TypePaiement::cases(),
                'choice_label' => fn(TypePaiement $choice) => $choice->value,
                'placeholder' => 'Sélectionner un type',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le type de paiement est obligatoire.']),
                ],
            ])
            ->add('montantP', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être supérieur à zéro.']),
                ],
            ])
            ->add('dateP', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date est obligatoire.']),
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date ne peut pas être dans le futur.',
                    ]),
                ],
            ])
            
            ->add('note', TextType::class, [
                'label' => 'Note (facultative)',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'La note ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('etudiant', EntityType::class, [
                'class' => Etudiant::class,
                'choice_label' => fn(Etudiant $e) => $e->getNom() . ' ' . $e->getPrenom(),
                'label' => 'Élève concerné',
                'placeholder' => 'Sélectionner un élève',
                'constraints' => [
                    new Assert\NotNull(['message' => 'L\'élève est obligatoire.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
        ]);
    }
}
