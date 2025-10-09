<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\EmployerTender;
use App\Entity\EmployerCourses;
use App\Form\CityAutocompleteField;
use App\Form\CountryAutoCompleteField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CourseFormType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'title',
                TextType::class,
                [
                    'attr' => ['class' => 'form-control'],
                ]
            )
            
            ->add(
                'course_duration',
                TextType::class,
                [
                    'attr' => ['class' => 'form-control'],
                ]
            )
            // ->add(
            //     'qualification',
            //     TextType::class,
            //     [
            //         'attr' => ['class' => 'form-control'],
            //     ]
            // )
            ->add('country', CountryAutoCompleteField::class, [
                'choice_label' => 'name',
                'required' => true,
                'attr' => [
                    'class' => 'form-select country_input'
                ],
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-select city_input'
                ],
            ])
            ->add('states', TextType::class, [
                'attr' => [
                    'class' => 'form-control state_input',
                ],
                'required' => false
            ])
            ->add('zip', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
           

            ->add('start_date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('attachment', FileType::class, [
                'required' => false,
                'data_class' => null,
                'constraints' => [
                    new File([
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid PDF document.',
                        'maxSize' => '2024k',
                        'maxSizeMessage' => 'The maximum allowed file size is 2MB.',
                    ])
                ],
            ])
            ->add('close_date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add(
                'external_link',
                TextType::class,
                [
                    'attr' => ['class' => 'form-control'],
                    'required' => false,
                ]
            )
            ->add(
                'price',
                TextType::class,
                [
                    'attr' => ['class' => 'form-control'],
                ]
            )
            ->add(
                'category',
                TextType::class,
                [
                    'attr' => ['class' => 'form-control'],
                ]
            )
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true
            ]);

            $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
            $builder->get('states')->addModelTransformer(new StateToNameTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployerCourses::class,
        ]);
    }
}
