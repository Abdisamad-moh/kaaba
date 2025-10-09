<?php

namespace App\Form;

use App\Entity\MetierCity;
use App\Entity\MetierState;
use App\Entity\MetierCountry;
use App\Entity\JobSeekerEducation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JobSeekerEducationsType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enrolled', ChoiceType::class, [
                'choices' => [
                    'Completed' => false,
                    'Still Enrolled' => true,
                ],
                'expanded' => true, // Render as radio buttons
                'multiple' => false, // Single choice
                'label' => 'Education Type',
                'attr' => [
                    'class' => 'enrolled-radio',
                ],
                'required' => true
            ])
            ->add('school', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter University/Institute name',
                ],
                'required' => true
            ])
            ->add('course', TextType::class, [
                'attr' => [
                    'placeholder' => 'Enter Course name',
                    'class' => 'form-control',
                ],
                'required' => true
            ])
            ->add('specialization', TextType::class, [
                'attr' => [
                    'placeholder' => 'Enter Specialization',
                    'class' => 'form-control',
                ]
            ])
            ->add('courseType', ChoiceType::class, [
                'choices' => [
                    'Full Time' => 'fulltime',
                    'Part Time' => 'part_time',
                    'Online Learning' => 'distant_learning',
                ],
                'expanded' => true, // Render as radio buttons
                'multiple' => false, // Single choice
                'attr' => [
                    'class' => 'enrolled-radio',
                ],
                'required' => true
            ])
            ->add('fromYear', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'required' => true
            ])
            ->add('toYear', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('address', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter full address',
                ],
                'required' => false
            ])
            // ->add('gradingSystem', TextType::class, [
            //     'attr' => [
            //         'class' => 'form-control',
            //         'placeholder' => 'Enter grading system',
            //     ],
            //     'required' => true
            // ])
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-md'
                ],
                'required' => false
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-select city_input'
                ],
            ])
            ->add('country', CountryAutoCompleteField::class, [
                'class' => MetierCountry::class,
                'choice_label' => 'name',
                'required' => true,
                'attr' => [
                    'class' => 'form-select country_input'
                ],
            ])
            ->add('state', TextType::class, [
                'attr' => [
                    'class' => 'form-control state_input',
                ],
                'required' => false
            ])
        ;

        $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
        $builder->get('state')->addModelTransformer(new StateToNameTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerEducation::class,
        ]);
    }
}
