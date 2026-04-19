<?php
// src/Form/StudentExcuseType.php

namespace App\Form;

use App\Entity\KaabaApplication;
use App\Entity\KaabaInstitute;
use App\Entity\KaabaStudentExcuse;
use App\Form\ApplicantAutoCompleteField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentExcuseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('application', EntityType::class, [
            //     'class' => KaabaApplication::class,
            //     'choice_label' => 'fullName',
            //     'placeholder' => 'Select Student',
            //     'required' => true,
            //     'attr' => ['class' => 'form-control student-select']
            // ])
            ->add('application', ApplicantAutoCompleteField::class, [
                'required' => true,
                'label' => 'Student Search',
                'placeholder' => 'Search by student id, name or phone',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('start_date', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('end_date', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('excuse_type', ChoiceType::class, [
                'choices' => [
                    'Sick Leave' => 'sick',
                    'Personal Leave' => 'personal',
                    'Family Emergency' => 'family',
                    'Travel' => 'travel',
                    'Exam/Test' => 'exam',
                    'Religious' => 'religious',
                    'Other' => 'other',
                ],
                'placeholder' => 'Select Excuse Type',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('reason', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Provide detailed reason for the excuse...'
                ]
            ])
            ->add('excused_hours_per_day', NumberType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.5',
                    'placeholder' => 'Leave empty to excuse full day'
                ],
                'help' => 'Enter hours to excuse (e.g., 4.5 for 4.5 hours). Leave empty to excuse full day.'
            ])
            ->add('admin_notes', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Internal notes (optional)'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KaabaStudentExcuse::class,
        ]);
    }
}