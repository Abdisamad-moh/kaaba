<?php
// src/Form/KaabaApplicationsFilterType.php

namespace App\Form;

use App\Entity\KaabaApplicationStatus;
use App\Entity\KaabaScholarship;
use App\Entity\KaabaInstitute;
use App\Entity\KaabaCourse;
use App\Entity\KaabaRegion;
use App\Entity\KaabaDistrict;
use App\Entity\KaabaQualification;
use App\Entity\KaabaGender;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class KaabaApplicationsFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $institutes = $options['institutes'];
        $courses = $options['courses'];

        $builder
            ->setMethod('GET')
            ->add('status', EntityType::class, [
                'class' => KaabaApplicationStatus::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Filter by Status',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('scholarship', EntityType::class, [
                'class' => KaabaScholarship::class,
                'choice_label' => 'title',
                'required' => false,
                'placeholder' => 'Filter by Scholarship',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('institute', EntityType::class, [
                'class' => KaabaInstitute::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Filter by Institute',
                'choices' => $institutes,
                'attr' => [
                    'class' => 'form-control institute-select',
                    'col_class' => 'col-md-3',
                    'data-dependent' => 'course'
                ],
            ])
            ->add('course', EntityType::class, [
                'class' => KaabaCourse::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select Course',
                'choices' => $courses,
                'attr' => [
                    'class' => 'form-control course-select',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('from_date', DateType::class, [
                'required' => false,
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
                'placeholder' => 'Filter by Region',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('district', EntityType::class, [
                'class' => KaabaDistrict::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Filter by District',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('qualification', EntityType::class, [
                'class' => KaabaQualification::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Filter by Qualification',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('gender', EntityType::class, [
                'class' => KaabaGender::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Filter by Gender',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3',
                ],
            ])
            ->add('limit', ChoiceType::class, [
                'required' => false,
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'user' => null,
            'institutes' => [],
            'courses' => [],
        ]);

        $resolver->setAllowedTypes('user', ['object', 'null']);
        $resolver->setAllowedTypes('institutes', 'array');
        $resolver->setAllowedTypes('courses', 'array');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}