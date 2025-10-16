<?php

namespace App\Form;

use App\Entity\KaabaCourse;
use App\Entity\KaabaGender;
use App\Entity\KaabaRegion;
use App\Entity\KaabaDistrict;
use App\Entity\KaabaInstitute;
use App\Entity\KaabaApplication;
use App\Entity\KaabaNationality;
use App\Entity\KaabaIdentityType;
use App\Entity\KaabaQualification;
use App\Entity\KaabaApplicationStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Contracts\Translation\TranslatorInterface;

class KaabaApplicationFormType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fileConstraints = [
            'maxSize' => '20M',
            'mimeTypes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'mimeTypesMessage' => 'Please upload a valid document (PDF, JPG, PNG, DOC, DOCX).',
        ];

// Get filtered regions and institutes from options
        $regions = $options['regions'] ?? [];
        $institutes = $options['institutes'] ?? [];

        $builder
            ->add('full_name', TextType::class, [
                'label' => $this->translator->trans('full_name'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('full_name_placeholder')
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Full name is required.'])
                ]
            ])
            ->add('date_of_birth', null, [
                'label' => $this->translator->trans('date_of_birth'),
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('date_placeholder')
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Date of birth is required.'])
                ]
            ])
            // ->add('town', TextType::class, [
            //     'label' => $this->translator->trans('town'),
            //     'required' => true,
            //     'attr' => [
            //         'class' => 'form-control',
            //         'placeholder' => $this->translator->trans('town_placeholder')
            //     ],
            //     'constraints' => [
            //         new NotBlank(['message' => 'Town is required.'])
            //     ]
            // ])
            ->add('village', TextType::class, [
                'label' => $this->translator->trans('village'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('village_placeholder')
                ]
            ])
            ->add('phone', TextType::class, [
                'label' => $this->translator->trans('phone'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('phone_placeholder')
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required.'])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('email'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('email_placeholder')
                ],
                'constraints' => [
                    new Email(['message' => 'Please enter a valid email address.'])
                ]
            ])
            ->add('disability', ChoiceType::class, [
    'label' => $this->translator->trans('disability_question'),
    'required' => true,
    'choices' => [
        $this->translator->trans('select_option') => '',
        $this->translator->trans('yes') => 'Yes',
        $this->translator->trans('no') => 'No',
        $this->translator->trans('prefer_not_to_say') => 'Prefer not to say'
    ],
    'attr' => [
        'class' => 'form-select'
    ],
    'constraints' => [
        new NotBlank(['message' => 'Please select an option for disability information.'])
    ]
])
->add('disability_type', ChoiceType::class, [
    'label' => $this->translator->trans('disability_type'),
    'required' => false,
    'choices' => [
        $this->translator->trans('select_disability_type') => '',
        $this->translator->trans('blind') => 'Blind',
        $this->translator->trans('physical') => 'Physical',
        $this->translator->trans('deaf') => 'Deaf',
        $this->translator->trans('learning_disability') => 'Learning Disability',
        $this->translator->trans('other') => 'Other'
    ],
    'attr' => [
        'class' => 'form-select border border-dark rounded-0'
    ]
])
->add('disability_explanation', TextareaType::class, [
    'label' => $this->translator->trans('disability_explanation'),
    'required' => false,
    'attr' => [
        'class' => 'form-control border border-dark rounded-0',
        'placeholder' => $this->translator->trans('disability_explanation_placeholder'),
        'rows' => 4,
    ]
])
            ->add('identity_type', EntityType::class, [
                'class' => KaabaIdentityType::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('identity_type'),
                'required' => false,
                'placeholder' => $this->translator->trans('identity_type_placeholder'),
                'attr' => [
                    'class' => 'form-select border border-dark rounded-0'
                ]
            ])
            ->add('identity_attachment', FileType::class, [
                'label' => $this->translator->trans('identity_attachment'),
                'required' => false,
                'mapped' => true,
                'constraints' => [
                    new File($fileConstraints)
                ],
                'attr' => [
                    'class' => 'hidden-file-input file-input',
                    'data-area' => 'identityArea'
                ]
            ])
            ->add('secondary_school', TextType::class, [
                'label' => $this->translator->trans('secondary_school'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('secondary_school_placeholder')
                ]
            ])
            ->add('secondary_graduation_year', TextType::class, [
                'label' => $this->translator->trans('secondary_graduation_year'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('secondary_graduation_year_placeholder')
                ]
            ])
            ->add('secondary_grade', TextType::class, [
                'label' => $this->translator->trans('secondary_grade'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('secondary_grade_placeholder')
                ]
            ])
            // ->add('highest_qualification_detail', TextType::class, [
            //     'label' => $this->translator->trans('highest_qualification_detail'),
            //     'required' => false, // Changed to false
            //     'attr' => [
            //         'class' => 'form-control',
            //         'placeholder' => $this->translator->trans('highest_qualification_detail_placeholder')
            //     ]
            // ])
            ->add('institution_name', TextType::class, [
                'label' => $this->translator->trans('institution_name'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('institution_name_placeholder')
                ]
            ])
            ->add('location', TextType::class, [
                'label' => $this->translator->trans('location'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('location_placeholder')
                ]
            ])
            ->add('start_year', TextType::class, [
                'label' => $this->translator->trans('start_year'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('start_year_placeholder')
                ]
            ])
            ->add('end_year', TextType::class, [
                'label' => $this->translator->trans('end_year'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('end_year_placeholder')
                ]
            ])
            ->add('qualification', TextType::class, [
                'label' => $this->translator->trans('qualification'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('qualification_placeholder')
                ]
            ])
            ->add('minimum_grade', TextType::class, [
                'label' => $this->translator->trans('minimum_grade'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('minimum_grade_placeholder')
                ]
            ])
            ->add('certificate_attachment', FileType::class, [
                'label' => $this->translator->trans('certificates_attachment'),
                'required' => false,
                'mapped' => true,
                'constraints' => [
                    new File($fileConstraints)
                ],
                'attr' => [
                    'class' => 'hidden-file-input file-input',
                    'data-area' => 'certificatesArea'
                ]
            ])
          
            ->add('region', EntityType::class, [
    'class' => KaabaRegion::class,
    'choice_label' => 'name',
    'label' => $this->translator->trans('region'),
    'required' => true,
    'choices' => $regions, // ✅ uses filtered regions
    'placeholder' => $this->translator->trans('region_placeholder'),
    'attr' => [
        'class' => 'form-select'
    ],
    'constraints' => [
        new NotBlank(['message' => 'Region is required.'])
    ]
])
            ->add('gender', EntityType::class, [
                'class' => KaabaGender::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('gender'),
                'required' => true,
 'placeholder' => $this->translator->trans('gender_placeholder')
,
                'attr' => [
                    'class' => 'form-select'                ],
                'constraints' => [
                    new NotBlank(['message' => 'Gender is required.'])
                ]
            ])
            ->add('district', EntityType::class, [
                'class' => KaabaDistrict::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('district'),
                'required' => false,
                'placeholder' => $this->translator->trans('district_placeholder'),
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('nationality', EntityType::class, [
                'class' => KaabaNationality::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('nationality'),
                'required' => false,
 'placeholder' => $this->translator->trans('nationality_placeholder')
,
                'attr' => [
                    'class' => 'form-select'                ]
            ])
            ->add('institute', EntityType::class, [
                'class' => KaabaInstitute::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('institute'),
                'required' => false,
                'placeholder' => $this->translator->trans('institute'),
                'choices' => $institutes, // Use filtered institutes
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            // ->add('secondary_region', EntityType::class, [
            //     'class' => KaabaRegion::class,
            //     'choice_label' => 'name',
            //     'label' => $this->translator->trans('secondary_region'),
            //     'required' => false, // Changed to false
            //     'attr' => [
            //         'class' => 'form-select'
            //     ]
            // ])
            // ->add('highest_qualification', EntityType::class, [
            //     'class' => KaabaQualification::class,
            //     'choice_label' => 'name',
            //     'placeholder' => $this->translator->trans('highest_qualification_placeholder'),
            //     'label' => $this->translator->trans('highest_qualification'),
            //     'required' => false,
            //     'attr' => [
            //         'class' => 'form-select'
            //     ]
            // ])
            ->add('course', EntityType::class, [
                'class' => KaabaCourse::class,
                'choice_label' => 'name',
                'label' => $this->translator->trans('course'),
                'required' => false, // Changed to false
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
           ->add('literacy_level', ChoiceType::class, [
    'label' => $this->translator->trans('literacy_level'),
    'required' => false,
    'choices' => [
        $this->translator->trans('literacy_no_skills') => $this->translator->trans('literacy_no_skills'),
        $this->translator->trans('literacy_limited') => $this->translator->trans('literacy_limited'),
        $this->translator->trans('literacy_moderate') => $this->translator->trans('literacy_moderate'),
        $this->translator->trans('literacy_proficient') => $this->translator->trans('literacy_proficient'),
    ],
    'placeholder' => $this->translator->trans('literacy_level_placeholder'),
    'attr' => [
        'class' => 'form-control',
    ]
])
->add('numeracy_level', ChoiceType::class, [
    'label' => $this->translator->trans('numeracy_level'),
    'required' => false,
    'choices' => [
        $this->translator->trans('numeracy_no_skills') => $this->translator->trans('numeracy_no_skills'),
        $this->translator->trans('numeracy_limited') => $this->translator->trans('numeracy_limited'),
        $this->translator->trans('numeracy_moderate') => $this->translator->trans('numeracy_moderate'),
        $this->translator->trans('numeracy_proficient') => $this->translator->trans('numeracy_proficient'),
    ],
    'placeholder' => $this->translator->trans('numeracy_level_placeholder'),
    'attr' => [
        'class' => 'form-control',
    ]
])
            ->add('recent_education', TextareaType::class, [
                'label' => $this->translator->trans('recent_education'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('recent_education_placeholder'),
                    'rows' => 3
                ]
            ])
            ->add('literacy_numeracy_qualification', TextType::class, [
                'label' => $this->translator->trans('literacy_numeracy_qualification'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => $this->translator->trans('literacy_numeracy_qualification_placeholder')
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KaabaApplication::class,
 'institutes' => [], // Add institutes as an option
    'regions' => [], // Add regions as an option
        ]);
    }
}
