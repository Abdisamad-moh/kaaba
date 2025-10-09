<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\EmployerDetails;
use App\Entity\MetierJobIndustry;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\DataTransformer\CityToNameTransformer;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

class EmployerDetailsFormType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            
            ->add('phone',TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                
            ])
            ->add('industry', EntityType::class, [
                'class' => MetierJobIndustry::class,
                'choice_label' => 'name',
                'placeholder' => 'Industry',
                'attr' => ['class' => 'form-select select2']
            ])
            
            ->add('website',TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('social_facebook',TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('social_twitter',TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('address',TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('zipcode',TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('social_linkedin',TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('number_of_employees',TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('company_stablishment_date', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description/Bio',
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control',
                ]
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'form-select city_input'
                ]
            ])
            ->add('state', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-select state_input'
                ]
            ])
            ->add('country', CountryAutoCompleteField::class, [
                'attr' => [
                    'class' => 'form-select country_input'
                ],
                'required' => true
            ])
        ;

        $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
        $builder->get('state')->addModelTransformer(new StateToNameTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployerDetails::class,
        ]);
    }
}
