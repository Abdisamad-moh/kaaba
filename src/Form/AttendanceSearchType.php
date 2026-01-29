<?php
// src/Form/AttendanceSearchType.php

namespace App\Form;

use App\Form\ApplicantAutoCompleteField;
use Symfony\Component\Form\AbstractType;
use App\Repository\KaabaInstituteRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AttendanceSearchType extends AbstractType
{
    private $instituteRepository;

    public function __construct(KaabaInstituteRepository $instituteRepository)
    {
        $this->instituteRepository = $instituteRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institutes = $options['institutes'] ?? $this->instituteRepository->findAll();
        
        // Create choices array
        $instituteChoices = [];
        foreach ($institutes as $institute) {
            $instituteChoices[$institute->getName()] = $institute->getId();
        }
        
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
            ->add('institute', ChoiceType::class, [
                'required' => false,
                'choices' => $instituteChoices,
                'placeholder' => 'All Institutes',
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-md-3'
                ],
                'label' => 'Institute'
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'institutes' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}