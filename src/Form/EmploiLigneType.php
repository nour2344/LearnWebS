<?php
namespace App\Form;

use App\Entity\EmploiLigne;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmploiLigneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b->add('day', ChoiceType::class, [
                'label'=>'Jour',
                'choices'=>['Lundi'=>'Mon','Mardi'=>'Tue','Mercredi'=>'Wed','Jeudi'=>'Thu','Vendredi'=>'Fri','Samedi'=>'Sat','Dimanche'=>'Sun'],
            ])
          ->add('startAt', TimeType::class, ['label'=>'Début','widget'=>'single_text'])
          ->add('endAt', TimeType::class,   ['label'=>'Fin','widget'=>'single_text'])
          ->add('matiere', TextType::class, ['label'=>'Matière']);
    }
    public function configureOptions(OptionsResolver $r): void
    { $r->setDefaults(['data_class'=>EmploiLigne::class]); }
}
