<?php

namespace App\Form;

use App\Entity\Etudiant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParentRegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom complet est requis.']),
                    new Assert\Length(['min' => 2, 'max' => 150]),
                ],
            ])
            ->add('phone', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est requis.']),
                    new Assert\Regex([
                        'pattern' => '/^\D*\d{8,15}\D*$/',
                        'message' => 'Le numéro doit contenir entre 8 et 15 chiffres.',
                    ]),
                ],
            ])
            ->add('child', EntityType::class, [
                'class' => Etudiant::class,
                'choice_label' => fn(Etudiant $e) => sprintf('%s %s (%s)', $e->getNom(), $e->getPrenom(), $e->getClasse()),
                'placeholder' => 'Sélectionner',
                'required' => false, // important: pas d’attribut HTML required (champ masqué)
                'constraints' => [
                    new Assert\NotNull(['message' => 'Sélectionnez votre enfant.']),
                ],
            ])
            ->add('smsCode', TextType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code SMS est requis.']),
                    new Assert\Length(['min' => 4, 'max' => 8]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir un mot de passe.']),
                    new Assert\Length(['min' => 8, 'minMessage' => 'Au moins {{ limit }} caractères.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Pas de data_class: on crée User + ParentProfile manuellement
        $resolver->setDefaults([]);
    }
}
