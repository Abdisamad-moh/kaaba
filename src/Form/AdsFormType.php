<?php

namespace App\Form;

use App\Entity\MetierAds;
use App\Entity\MetierAdPage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class AdsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Active' => true,
                    'In-Active' => false,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('client', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('deadline', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('link', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'label' => "E.g Website, Facebook/Instagram page etc",
            ])
            ->add('contact_email', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file.',
                        'maxSize' => '2024k',
                        'maxSizeMessage' => 'The maximum allowed file size is 2MB.',
                    ])
                ],
            ])
            ->add('pages', EntityType::class, [
                'class' => MetierAdPage::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false, // set to true for checkboxes
                'required' => false,
                'attr' => ['class' => 'form-select', 'multiple' => true],
                'label' => 'Pages to Post On',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierAds::class,
        ]);
    }
}
