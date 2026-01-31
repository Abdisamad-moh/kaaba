<?php
// src/Form/AttendanceSearchType.php

namespace App\Form;

use App\Form\ApplicantAutoCompleteField;
use Symfony\Component\Form\AbstractType;
use App\Repository\KaabaInstituteRepository;
use App\Repository\KaabaCourseRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AttendanceSearchType extends AbstractType
{
    private $instituteRepository;
    private $courseRepository;

    public function __construct(
        KaabaInstituteRepository $instituteRepository,
        KaabaCourseRepository $courseRepository
    ) {
        $this->instituteRepository = $instituteRepository;
        $this->courseRepository = $courseRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institutes = $options['institutes'] ?? $this->instituteRepository->findAll();
        
        $builder
            ->add('date', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3'
                ],
                'label' => 'Select Date'
            ])
            ->add('applicant', ApplicantAutoCompleteField::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Applicant Search',
                'placeholder' => 'Search by applicant id, name or phone',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('institute', EntityType::class, [
                'class' => 'App\Entity\KaabaInstitute',
                'choice_label' => 'name',
                'required' => false,
                'choices' => $institutes,
                'placeholder' => 'All Institutes',
                'attr' => [
                    'class' => 'form-control institute-select',
                    'col_class' => 'col-md-3'
                ],
                'label' => 'Institute'
            ])
            ->add('course', ChoiceType::class, [
                'required' => false,
                'choices' => [],
                'placeholder' => 'Select institute first',
                'attr' => [
                    'class' => 'form-control course-select',
                    'col_class' => 'col-md-3'
                ],
                'label' => 'Course'
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'All Status' => '',
                    'Present' => 'present',
                    'Absent' => 'absent',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3'
                ],
                'label' => 'Status'
            ]);
            
        // Handle institute change to populate courses on form load (initial page load)
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();
            $data = $event->getData();
            
            // Initialize with empty courses - will be populated by JavaScript
            $form->add('course', ChoiceType::class, [
                'required' => false,
                'choices' => [],
                'placeholder' => 'Select institute first',
                'attr' => [
                    'class' => 'form-control course-select',
                    'col_class' => 'col-md-3'
                ],
                'label' => 'Course'
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'institutes' => null,
            'request' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}