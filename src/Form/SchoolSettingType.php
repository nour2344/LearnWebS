<?php
// src/Form/SchoolSettingType.php
namespace App\Form;

use App\Entity\SchoolSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class SchoolSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => "Nom de l'établissement",
                'constraints' => [new NotBlank()],
            ])
            ->add('primaryColor', ColorType::class, [
                'label' => 'Couleur principale',
                'required' => false,
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo (PNG/JPG, max 2MB)',
                'mapped' => false, // not associated directly to entity field
                'required' => false,
                'constraints' => [
                    new File(maxSize: '2M', mimeTypes: ['image/png','image/jpeg'])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SchoolSetting::class]);
    }
}
