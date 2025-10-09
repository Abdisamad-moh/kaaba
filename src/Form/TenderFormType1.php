<?php

namespace App\Form;

use App\Entity\EmployerJobs;
use App\Entity\MetierCareers;
use App\Entity\MetierCity;
use App\Entity\MetierCountry;
use App\Entity\MetierJobCategory;
use App\Entity\MetierJobType;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TenderFormType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
         
           
            ->add('tender_experience_skills', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 4],
                'label' => 'Skill and Experience Requirements',
            ])
            ->add('scope', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('location', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])
            
           
            // ->add('other_requirements', TextareaType::class, [
            //     'attr' => ['class' => 'form-control']
            // ])
            ->add('application_closing_date', null, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            // ->add('job_category', EntityType::class, [
            //     'class' => MetierJobCategory::class,
            //     'choice_label' => 'id',
            // ])
            // ->add('city', CityAutocompleteField::class, [
            //     'attr' => [
            //         'class' => 'form-control'
            //     ]
            // ])
            // ->add('country', CountryAutoCompleteField::class, [
            //     'attr' => [
            //         'class' => 'form-control'
            //     ]
            // ])
            ->add('Post', SubmitType::class, [
                'attr' => ['class' => 'btn-primary btn my-2'],
            ])
            ->add('tender_title', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('company_name', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('contact_name', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('contact_email', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('contact_phone', TextType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            // ->add('job_type', EntityType::class, [
            //     'class' => MetierJobType::class,
            //     'autocomplete' => true,
            //     'placeholder' => 'Select',
            //     'choice_label' => 'name',
            //     'attr' => ['class' => 'form-control']
            // ])
            ->add('tender_duration', TextType::class, [
                'attr' => ['class' => 'form-control']
            ])

            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployerJobs::class,
        ]);
    }
}
