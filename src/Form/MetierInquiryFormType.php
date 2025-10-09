<?php

namespace App\Form;

use App\Entity\MetierCountry;
use App\Entity\MetierInquiry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class MetierInquiryFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('company_name', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('contact_person', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('email', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('phone', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('address', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('job_title', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('department', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('purpose_of_evaluation', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Recruitment' => "Recruitment",
                    'Employee Development' => "Employee Development",
                    'Team Building' => "Team Building",
                    'Leadership Assessment' => "Leadership Assessment",
                    'Other' => "Other",
                ],
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('estimated_budget', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('payment_method', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'form-control required ']
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control required',
                    'rows' => 10
                    ]
            ])
            ->add('country', CountryAutoCompleteField::class, [
                'attr' => [
                    'class' => 'form-select country_input'
                ],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierInquiry::class,
        ]);
    }
}
