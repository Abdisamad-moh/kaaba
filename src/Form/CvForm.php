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
use App\Model\ResumeStatusEnum;
use App\Entity\MetierJobCategory;
use App\Entity\MetierJobIndustry;
use App\Form\StatesAutoCompleteField;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CvForm extends AbstractType
{
    public function __construct(private EntityManagerInterface $em) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            // ->add('location', TextType::class, [
            //     'required' => false,
            //     'attr' => ['class' => 'form-control']
            // ])
            ->add('experience', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    'Choose Experience' => null,
                    'Entry-level (0-2 years)' => 0,
                    'Intermediate or Mid-level (3-5 years)' => 1,
                    'Senior-level (6-8 years)' => 2,
                    'Managerial-level (9-12 years)' => 3,
                    'Director-level (13-15 years)' => 4,
                    'Executive-level (16+ years)' => 5,
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('education', ChoiceType::class, [
                'required' => false,
                'choices'  => [
                    'Choose Required Education' => null,
                    'Secondary/ High School' => 1,
                    'Diploma/Associate Degree' => 2,
                    'Bachelor\'s Degree' => 3,
                    'Master\'s Degree' => 4,
                    'Doctorate/ PhD' => 5,
                    'Domestic/Manual Worker' => 0,
                ],
                'attr' => ['class' => 'form-control']
            ])


            ->add('city', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Select City',
                    'class' => 'form-select city_input'
                ]
            ])
            ->add('states', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-select state_input',
                    'placeholder' => 'Select State',
                ]
            ])
            ->add('country', CountryAutoCompleteField::class, [
                'attr' => [
                    'class' => 'form-select country_input'
                ],
                'required' => false,
            ])

            ->add('jobtitle', CareerAutoCompleteField::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('careerStatus', ChoiceType::class, [
                'placeholder' => 'Choose career status',
                'choices' => ResumeStatusEnum::cases(),
                'choice_label' => fn(ResumeStatusEnum $status) => $status->value,
                'choice_value' => fn(?ResumeStatusEnum $status) => $status?->value,
                'attr' => [
                    'class' => 'form-select'
                ],
                'required' => false,
            ])
           
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
            // 'data_class' => EmployerJobs::class,
        ]);
    }
    
}
