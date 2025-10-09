<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierSkills;
use App\Entity\MetierCareers;
use App\Entity\JobSeekerResume;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JobSeekerResumeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('education', ChoiceType::class, [
                'placeholder' => 'Select your education level',
                'choices' => [
                    'Secondary/ High School' => 1,
                    'Diploma/Associate Degree' => 2,
                    'Bachelor\'s Degree' => 3,
                    'Master\'s Degree' => 4,
                    'Doctorate/ PhD' => 5,
                    'Domestic/Manual Worker' => 0,
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('skills', TextType::class, [
                'attr' => [
                    'class' => 'form-select',
                ],
                'mapped' => false,
                'required' => true,
            ])
            ->add('jobTitle', CareerAutoCompleteField::class, [
                
            ])
            ->add('experience', ChoiceType::class, [
                'placeholder' => 'Select Experience',
                'choices' => [
                    'Entry-level (0-2 years)' => 0,
                    'Intermediate or Mid-level (3-5 years)' => 1,
                    'Senior-level (6-8 years)' => 2,
                    'Managerial-level (9-12 years)' => 3,
                    'Director-level (13-15 years)' => 4,
                    'Executive-level (16+ years)' => 5,
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'mapped' => true
            ])
            ->add('willingToRelocate', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false
                ],
                'required' => true,
                'placeholder' => 'Choose',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            ->add('linkedin', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('portfolio', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerResume::class,
        ]);

    }
}
