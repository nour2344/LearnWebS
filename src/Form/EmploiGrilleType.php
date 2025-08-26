<?php
namespace App\Form;

use App\Entity\EmploiTemps;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmploiGrilleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b->add('classe', TextType::class, ['label'=>'Classe'])
          ->add('titre', TextType::class, ['label'=>'Titre (optionnel)','required'=>false])
          ->add('effectiveFrom', DateType::class, ['label'=>'Applicable à partir du','widget'=>'single_text']);
    }
    public function configureOptions(OptionsResolver $r): void
    { $r->setDefaults(['data_class'=>EmploiTemps::class]); }
}
