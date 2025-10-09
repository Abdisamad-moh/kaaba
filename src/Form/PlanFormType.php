<?php

namespace App\Form;

use App\Entity\MetierSubscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PlanFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('description',TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type',ChoiceType::class, [
                'choices'  => [
                    'Choose Type' => null,
                    'Employer' => "employer",
                    'Job Seeker' => "job seeker",
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('amount',TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('display_order',NumberType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => true,
            ])
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Choose Status' => null,
                    'Active' => true,
                    'In-Active' => false,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierSubscription::class,
        ]);
    }
}
