<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierCity;
use App\Entity\MetierCountry;
use App\Entity\JobSeekerCertificate;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JobSeekerCertificateType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter certificate name'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'The name field cannot be empty.',
                    ]),
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'The name must be a string.',
                    ]),
                ],
                'required' => true,
            ])
            ->add('institute', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter institution name'
                ],
                'required' => true,
            ])
            ->add('certificateId', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter certificate id'
                ],
                'required' => true,
            ])
            ->add('certificateUrl', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter certificate url if any'
                ],
                'required' => true,
            ])
            ->add('file', FileType::class, [
                'required' => false,
                'data_class' => null,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document.',
                        'maxSize' => '2024k',
                        'maxSizeMessage' => 'The maximum allowed file size is 2MB.',
                    ])
                ],
            ])
            ->add('expirable', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'expanded' => true, // Render as radio buttons
                'multiple' => false, // Single choice
                'attr' => [
                    'class' => 'enrolled-radio',
                    // 'data-controller' => 'jobseeker-profile',
                    'data-action' => 'change->jobseeker-profile#toggleCertificateExpirable',
                ],
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
            ->add('expiresAt', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);

        $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
        
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            // Convert null to empty string for text fields
            foreach (['name', 'institute', 'certificateId', 'certificateUrl'] as $field) {
                if (!isset($data[$field]) || $data[$field] === null) {
                    $data[$field] = '';
                }
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerCertificate::class,
        ]);
    }
}
