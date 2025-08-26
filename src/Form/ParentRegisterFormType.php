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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 150),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'mapped' => false,
                'attr' => ['inputmode' => 'numeric', 'pattern' => '\d*'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex('/^\d{8,15}$/', 'Le numéro doit contenir entre 8 et 15 chiffres.'),
                ],
            ])
           ->add('child', EntityType::class, [
    'class'        => Etudiant::class,
    'label'        => 'Votre enfant',
    'placeholder'  => 'Choisissez votre enfant…',
    'mapped'       => false,
    'required'     => false,          // ⬅ plus "required" côté HTML
    'choice_label' => fn(Etudiant $e) => sprintf('%s %s — %s', $e->getNom(), $e->getPrenom(), $e->getClasse()),
    'choice_value' => 'id',
    'attr'         => ['class' => 'd-none'],
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

        /**
         * Cross-field validation AFTER data is mapped:
         * - Compare fullName vs child.nomPere / child.nomMere (normalized)
         * - Compare phone (digits) vs child.numTel / child.numTel2 (digits)
         */
        $b->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            /** @var Etudiant|null $child */
            $child = $form->get('child')->getData();
            $fullName = (string) $form->get('fullName')->getData();
            $phone    = (string) $form->get('phone')->getData();

            // Normalizers
            $normName = static function (?string $s): string {
                return preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $s)));
            };
            $digits = static function (?string $s): string {
                return preg_replace('/\D+/', '', (string) $s);
            };

            // If no child selected, base constraint will already add an error.
            if (!$child instanceof Etudiant) {
                return;
            }

            $inputName  = $normName($fullName);
            $inputPhone = $digits($phone);

            $allowedNames = array_filter([
                $normName($child->getNomPere()),
                $normName($child->getNomMere()),
            ], static fn($v) => $v !== '');

            $allowedPhones = array_values(array_filter([
                $digits($child->getNumTel()),
                $digits($child->getNumTel2()),
            ], static fn($v) => $v !== ''));

            if (!in_array($inputName, $allowedNames, true)) {
                $form->get('fullName')->addError(new FormError(
                    'Le nom saisi doit correspondre au père ou à la mère enregistrés pour cet élève.'
                ));
            }

            if (!in_array($inputPhone, $allowedPhones, true)) {
                $form->get('phone')->addError(new FormError(
                    'Le téléphone doit correspondre à l’un des numéros enregistrés pour cet élève.'
                ));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
