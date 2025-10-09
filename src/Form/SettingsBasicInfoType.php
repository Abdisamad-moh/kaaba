<?php

namespace App\Form;

use App\Entity\MetierAppSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class SettingsBasicInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('phone', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('whatsapp', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('address', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('logo', FileType::class, [
                'label' => 'Choose Logo (image file)',
    
                // unmapped means that this field is not associated to any entity property
                'mapped' => false,
    
                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,
    
                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image document',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierAppSetting::class,
        ]);
    }
}
