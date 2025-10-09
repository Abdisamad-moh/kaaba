<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierCity;
use App\Entity\MetierState;
use App\Entity\MetierCountry;
use App\Entity\MetierJobType;
use App\Entity\MetierCurrency;
use App\Entity\JobSeekerExperience;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Range;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class JobSeekerExperienceType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        
        $builder
            ->add('current', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true, // Render as radio buttons
                'multiple' => false, // Single choice
                'attr' => [
                    'class' => 'enrolled-radio',
                    // 'data-controller' => 'jobseeker-profile',
                    // 'data-action' => 'jobseeker-profile#toggleEmploymentDetails',
                    'data-action' => 'change->jobseeker-profile#toggleExperienceIsCurrent',
                ],
                'required' => true,
            ])
            ->add('experienceYears', ChoiceType::class, [
                'choices' => array_combine(range(0, 30), range(0, 30)),
                'placeholder' => 'Years',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
            ->add('experienceMonths', ChoiceType::class, [
                'choices' => array_combine(range(1, 12), range(1, 12)),
                'placeholder' => 'Months',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ])
            ->add('companyName', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Company Name',
                ],
                'required' => true,
            ])
            ->add('noticePeriod', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ])
            ->add('joinedDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
            ->add('finishDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ])
            ->add('salary', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'constraints' => [
                    // new NotBlank([
                    //     'message' => 'Please upload an image file.',
                    // ]),
                    new Range([
                        'min' => 0, // Minimum rating (inclusive)
                        'max' => 200000, // Maximum rating (inclusive)
                        'notInRangeMessage' => 'Please enter a valid number.', // Custom message for values out of range
                        'invalidMessage' => 'please enter a valid number.', // Custom message for invalid inputs
                    ]),
                ],
            
            ])
            ->add('jobType', EntityType::class, [
                'placeholder' => 'Select job type',
                'class' => MetierJobType::class,
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
                'autocomplete' => true,
                
            ])
            ->add('duties', CKEditorType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ],
                'config' => [
                    'toolbar' => 'basic', // You can specify toolbar configuration here
                ],
            ])
            ->add('zipCode', TextType::class, [
                'attr' => [
                    'class' => 'form-control form-control-md'
                ],
                'required' => false
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-select city_input',
                    'placeholder' => 'Select city'
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
                    'placeholder' => 'Select state/region'
                ],
                'required' => false
            ])
            ->add('positionName', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Type position name',
                    'data-career-autocomplete-target' => 'careerInput',
                ]
            ])
            ->add('currency', EntityType::class, [
                'class' => MetierCurrency::class,
                'choice_label' => function (MetierCurrency $entity) {
                    // Custom logic to display the label, like adding muted text
                    return $entity->getSymbol() . '' . $entity->getCode();
                },
                'attr' => [
                    'class' => 'form-select form-select-lg'
                ],
                'required' => false,
                'placeholder' => 'Currency',
                'autocomplete' => true,
            ])
            ;

            $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
            $builder->get('state')->addModelTransformer(new StateToNameTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerExperience::class,
        ]);
    }
}
