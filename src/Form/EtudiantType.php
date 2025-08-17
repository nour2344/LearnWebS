<?php

namespace App\Form;

use App\Entity\Etudiant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;

class EtudiantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $today = (new \DateTime())->format('Y-m-d');

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('nomP', TextType::class, [
                'label' => 'Nom Parent',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('classe', TextType::class, [
                'label' => 'Classe',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La classe est obligatoire.']),
                    new Assert\Length([
                        'min' => 1,
                        'max' => 50,
                        'minMessage' => 'La classe doit contenir au moins {{ limit }} caractère.',
                        'maxMessage' => 'La classe ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('dateN', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['max' => $today],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de naissance est obligatoire.']),
                    new Assert\LessThan([
                        'value' => 'today',
                        'message' => 'La date de naissance doit être antérieure à aujourd\'hui.',
                    ]),
                ],
            ])
            ->add('dateInscription', DateType::class, [
                'label' => 'Date d\'inscription',
                'widget' => 'single_text',
                'html5' => true,
                'required' => true, // <-- required
                'attr' => ['max' => $today], // client-side guard
                'invalid_message' => 'La date d\'inscription est invalide.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'inscription est obligatoire.']), // <-- server-side guard
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date d\'inscription ne peut pas être dans le futur.',
                    ]),
                ],
            ])
            ->add('numTel', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro de téléphone est obligatoire.']),
                    new Assert\Regex([
                        'pattern' => '/^\d{8,15}$/',
                        'message' => 'Le numéro de téléphone doit contenir entre 8 et 15 chiffres.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Etudiant::class,
        ]);
    }
}
