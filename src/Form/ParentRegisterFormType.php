<?php

namespace App\Form;

use App\Entity\Etudiant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParentRegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('fullName', TextType::class, [
                'label' => 'Votre nom complet',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 150)],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 30)],
            ])
            ->add('child', EntityType::class, [
                'class' => Etudiant::class,
                'label' => 'Votre enfant',
                'placeholder' => 'Choisissez votre enfant…',
                'mapped' => false,
                'choice_label' => fn(Etudiant $e) => sprintf('%s %s — %s', $e->getNom(), $e->getPrenom(), $e->getClasse()),
                'constraints' => [new Assert\NotNull(message: 'Choisissez votre enfant')],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 6, max: 4096)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
