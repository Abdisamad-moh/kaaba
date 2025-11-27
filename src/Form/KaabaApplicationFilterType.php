<?php

namespace App\Form;

use App\Entity\KaabaGender;
use App\Entity\KaabaRegion;
use App\Entity\KaabaDistrict;
use App\Entity\KaabaInstitute;
use App\Entity\KaabaScholarship;
use App\Entity\KaabaQualification;
use App\Entity\KaabaApplicationStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class KaabaApplicationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
              ->add('status', EntityType::class, [
            'class' => KaabaApplicationStatus::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'label' => 'Status',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Status'
            ],
        ])
        ->add('scholarship', EntityType::class, [
            'class' => KaabaScholarship::class,
            'choice_label' => 'title',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Scholarship'
            ],
        ])
               ->add('institute', EntityType::class, [
            'class' => KaabaInstitute::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'form-control institute-select',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Institute',
                'data-dependent' => 'course'
            ],
        ])
     ->add('course', ChoiceType::class, [
    'required' => false,
    'mapped' => false,
    'choice_label' => 'name',
    'choice_value' => 'id',
    'placeholder' => 'Select Course',
    'attr' => [
        'class' => 'form-control course-select',
        'col_class' => 'col-md-3',
    ],
])
        ->add('from_date', DateType::class, [
            'required' => false,
            'mapped' => false,
            'widget' => 'single_text',
            'label' => 'From Date',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'From Date'
            ],
        ])
        ->add('to_date', DateType::class, [
            'required' => false,
            'mapped' => false,
            'widget' => 'single_text',
            'label' => 'To Date',
            
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'To Date'
            ],
        ])
        ->add('phone', TextType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'Phone Number',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Phone'
            ],
        ])
        ->add('region', EntityType::class, [
            'class' => KaabaRegion::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Region'
            ],
        ])
        ->add('district', EntityType::class, [
            'class' => KaabaDistrict::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by District'
            ],
        ])
        ->add('qualification', EntityType::class, [
            'class' => KaabaQualification::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Qualification'
            ],
        ])
        ->add('gender', EntityType::class, [
            'class' => KaabaGender::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Gender'
            ],
        ])
        ->add('limit', ChoiceType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'Items per Page',
            'choices' => [
                '25' => 25,
                '50' => 50,
                '100' => 100,
                '200' => 200,
                '500' => 500,
            ],
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-2',
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
