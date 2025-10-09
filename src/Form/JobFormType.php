<?php

namespace App\Form;

use App\Entity\MetierCity;
use App\Entity\MetierState;
use App\Entity\EmployerJobs;
use App\Entity\MetierSkills;
use App\Entity\MetierBenefit;
use App\Entity\MetierCareers;
use App\Entity\MetierCountry;
use App\Entity\MetierJobType;
use App\Entity\MetierCurrency;
use App\Entity\MetierWorkShift;
use App\Entity\MetierJobCategory;
use App\Entity\MetierJobIndustry;
use App\Form\StatesAutoCompleteField;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class JobFormType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('number_position', NumberType::class, [
                'attr' => ['class' => 'form-control numberClass'],
                'required' => true,
            ])
            ->add('work_type', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Work From Office' => "Work From Office",
                    'Hybrid' => "Hybrid",
                    'Remote Job' => "Remote Job",
                ],
                'placeholder' => 'Choose Work Type',

                'attr' => ['class' => 'form-control required ']
            ])
            ->add('has_commission', ChoiceType::class, [
                'required' => false,
                'label' => 'Commission',
                'choices'  => [
                    'No' => false,
                    'Yes' => true,
                ],
                'placeholder' => 'Commission',
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('job_description', CKEditorType::class, [
                'attr' => ['class' => 'form-control required '],
                'config' => [
                    'toolbar' => 'basic', // You can specify toolbar configuration here
                ],
            ])
            ->add('minimum_pay', NumberType::class, [
                'data' => 0.00,
                'scale' => 2, // Allows up to 2 decimal places
                'html5' => true, // Enables HTML5 input validation
                'attr' => [
                    'class' => 'form-control numberClass',
                    'step' => '0.01', // Ensures the input accepts decimal values
                ],
            ])
            ->add('maximum_pay', NumberType::class, [
                'data' => 0.00,
                'scale' => 2,
                'html5' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-control numberClass',
                    'step' => '0.01',
                ],
            ])
            ->add('hours', NumberType::class, [
                'attr' => ['class' => 'form-control numberClass '],
                'required' => true,
            ])

            ->add('currency', EntityType::class, [
                'class' => MetierCurrency::class,
                'choice_label' => function ($course) {
                    return $course->getName() . ' - ' . $course->getCode();
                },
                'placeholder' => 'Currency',
                'attr' => ['class' => 'form-control '],
                'required' => false,
            ])
            ->add('mentioned_amount_by', ChoiceType::class, [
                'choices'  => [
                    'Hourly' => "Hourly",
                    'Daily' => "Daily",
                    'Weekly' => "Weekly",
                    'By Weekly' => "By Weekly",
                    'Monthly' => "Monthly",
                    'Yearly' => "Yearly",
                ],
                'attr' => ['class' => 'form-control '],
                'required' => false,
                'placeholder' => 'Select Salary Frequency',
            ])

            ->add('location', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control ']
            ])

            ->add('education_required', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Secondary/ High School' => 1,
                    'Diploma/Associate Degree' => 2,
                    'Bachelor\'s Degree' => 3,
                    'Master\'s Degree' => 4,
                    'Doctorate/ PhD' => 5,
                    'No Diploma Required' => 0,
                ],
                'attr' => ['class' => 'form-control required ']
            ])

            ->add('certification_required', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Yes' => 1,
                    'No' => 0,
                ],
                'attr' => [
                    'class' => 'form-control required ',
                    'data-action' => 'change->employer#setCertification',
                ],
                'placeholder' => 'Certification Required',
            ])
            ->add('certifications', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control  certification-field',
                    'readonly' => true,
                ]
            ])

            ->add('other_requirements', CKEditorType::class, [
                'attr' => ['class' => 'form-control required ','placeholder' => 'Type N/A if not applicable',],
                'config' => [
                    'toolbar' => 'basic', // You can specify toolbar configuration here
                ],
                
            ])
            ->add('application_closing_date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,  // Enables the HTML5 date input widget
                'attr' => [
                    'class' => 'form-control required',
                    'min' => (new \DateTime('tomorrow'))->format('Y-m-d'), // Set the minimum date to tomorrow
                ],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => (new \DateTime('tomorrow'))->format('Y-m-d'),
                        'message' => 'The application closing date must be from tomorrow onward.',
                    ]),
                ],
            ])
            ->add('job_category', EntityType::class, [
                'class' => MetierJobCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Job Category',
                'attr' => ['class' => 'select2 form-select']
            ])

            ->add('industry', EntityType::class, [
                'class' => MetierJobIndustry::class,
                'choice_label' => 'name',
                'placeholder' => 'Industry',
                'attr' => ['class' => 'form-select'],
                'autocomplete' => true
            ])

            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'form-select city_input'
                ],
                'required' => true,
            ])
            ->add('states', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-select state_input'
                ]
            ])
            ->add('country', CountryAutoCompleteField::class, [
                'attr' => [
                    'class' => 'form-select country_input'
                ],
                'required' => true,
            ])

            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-select'
                ],
                'required' => true,
            ])
            ->add('job_types', EntityType::class, [
                'class' => MetierJobType::class,
                'autocomplete' => true,
                'placeholder' => 'Select',
                'choice_label' => 'name',
                'multiple' => true,
                'attr' => ['class' => 'form-control required ', 'placeholder' => 'e.g. Full-Time'],
                'required' => true,
            ])
            ->add('benefits', EntityType::class, [
                'class' => MetierBenefit::class,
                'autocomplete' => true,
                'placeholder' => 'Select',
                'choice_label' => 'name',
                'multiple' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            // ->add('required_skill', EntityType::class, [
            //     'class' => MetierSkills::class,
            //     'autocomplete' => true,
            //     'placeholder' => 'Select',
            //     'choice_label' => 'name',
            //     'multiple' => true,
            //     'attr' => ['class' => 'form-control required ']
            // ])
            ->add('required_skill', TextType::class, [
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'skill_input form-select'
                ]
            ])
            ->add('preferred_skill', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'skill_input form-select'
                ]
            ])
            ->add('education_titles', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('experience', ChoiceType::class, [
                'choices'  => [
                    'Entry-level (0-2 years)' => 0,
                    'Intermediate or Mid-level (3-5 years)' => 1,
                    'Senior-level (6-8 years)' => 2,
                    'Managerial-level (9-12 years)' => 3,
                    'Director-level (13-15 years)' => 4,
                    'Executive-level (16+ years)' => 5,
                ],
                'placeholder' => 'e.g. Entry-level (0-2 years)',
                'attr' => ['class' => 'form-control required '],
                'required' => true,
                // 'autocomplete' => true
            ])
            ->add('shift', EntityType::class, [
                'class' => MetierWorkShift::class,
                'autocomplete' => true,
                'placeholder' => 'Work shift',
                'choice_label' => 'name',
                'multiple' => true,
                'required' => true,
                'attr' => ['class' => 'form-control required ', 'placeholder' => 'Select Work Shift'],
            ])
            ->add('show_salary', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Display Salary',
            ])
            ->add('requireCoverLetter', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Require Cover Letter',
            ])
            ->add('is_private', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Internal Job',
            ])
            ->add('immediate_hiring', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => ['class' => 'form-control required '],
                'placeholder' => 'Immediate Hiring',
            ])
            ->add('locals_only', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => ['class' => 'form-control required '],
                'placeholder' => 'Locals Only',
            ])
            ->add('external_link', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('zipe_code', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            // ->add('employerJobQuestions', LiveCollectionType::class, [
            //     'entry_type' => JobQuestionType::class,
            //     'entry_options' => ['label' => false],
            //     'label' => false,
            //     'allow_add' => true,
            //     'allow_delete' => true,
            //     'by_reference' => false,
            //     // 'disabled' => true,
            // ])
            // ->getEventDispatcher()->addListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);

            // ->add('Post', SubmitType::class, [
            //     'attr' => ['class' => 'btn-primary btn'],
            // ])

        ;

        $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
        $builder->get('states')->addModelTransformer(new StateToNameTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployerJobs::class,
        ]);
    }
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($data['certification_required'] !== 'Yes') {
            $form->remove('certifications');
        }
    }
}
