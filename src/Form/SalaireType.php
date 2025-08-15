<?php

namespace App\Form;

use App\Entity\Salaire;
use App\Entity\Personnel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SalaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = (new \DateTime())->format('Y-m-d');

        $builder
            ->add('mois', TextType::class, [
                'label' => 'Mois (ex : Août 2025)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le mois est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Le mois doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le mois ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant du salaire',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant est obligatoire.']),
                    new Assert\Positive(['message' => 'Le montant doit être supérieur à zéro.']),
                ],
            ])
            ->add('dateP', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'required' => true,
                'html5' => true,
                'attr' => ['max' => $today],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de paiement est obligatoire.']),
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date ne peut pas être dans le futur.',
                    ]),
                ],
            ])
            ->add('personnel', EntityType::class, [
                'label' => 'Personnel concerné',
                'class' => Personnel::class,
                'choice_label' => fn (Personnel $p) => $p->getNomP() . ' ' . $p->getPrenomP(),
                'placeholder' => 'Sélectionnez un personnel',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le personnel est obligatoire.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Salaire::class,
        ]);
    }
}
