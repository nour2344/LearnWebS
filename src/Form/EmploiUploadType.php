<?php
namespace App\Form;

use App\Entity\EmploiTemps;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EmploiUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b->add('classe', TextType::class, ['label'=>'Classe'])
          ->add('titre', TextType::class, ['label'=>'Titre (optionnel)', 'required'=>false])
          ->add('effectiveFrom', DateType::class, ['label'=>'Applicable à partir du','widget'=>'single_text'])
          ->add('upload', FileType::class, [
              'label'=>'Fichier (PDF/PNG/JPEG/WEBP)', 'mapped'=>false, 'required'=>$o['upload_required'],
              'constraints'=>[new Assert\File([
                  'maxSize'=>'8M','mimeTypes'=>['application/pdf','image/png','image/jpeg','image/webp']
              ])],
          ]);
    }
    public function configureOptions(OptionsResolver $r): void
    { $r->setDefaults(['data_class'=>EmploiTemps::class,'upload_required'=>true]); }
}
