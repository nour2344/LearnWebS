<?php

namespace App\Form;

use App\Entity\Personnel;
use App\Enum\RolePersonnel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;

class PersonnelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomP', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prenomP', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => RolePersonnel::cases(),
                'choice_label' => fn(RolePersonnel $role) => $role->value,
                'placeholder' => 'Sélectionnez un rôle',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le rôle est obligatoire.']),
                ],
            ])
            ->add('salaire', MoneyType::class, [
                'label' => 'Salaire',
                'currency' => 'DINAR',
                  'attr' => ['step' => '0.01', 'min' => '0.01', 'inputmode' => 'decimal'],

                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le salaire est obligatoire.']),
                    new Assert\Positive(['message' => 'Le salaire doit être supérieur à zéro.']),
                ],
            ])
            ->add('dateRecrutement', DateType::class, [
  'widget' => 'single_text',
  'html5' => true,
  'attr' => ['max' => (new \DateTime())->format('Y-m-d')],
                'required' => false,
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de recrutement ne peut pas être dans le futur.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personnel::class,
        ]);
    }
}
