<?php

namespace App\Form;

use App\Entity\JobApplicationInterview;
use Symfony\Component\Form\AbstractType;
use App\Entity\JobApplicationInterviewRound;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class RoundFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices'  => [
                    'Move to next round' => "next round",
                    'Rejected' => "rejected",
                    'Selected' => "selected",
                ],
                'attr' => [
                    'class' => 'btn-check',
                    'data-action' => 'interview-result#change'
                ],
                'label' => false,
                'choice_attr' => function($choice, $key, $value) {
                    return ['data-action' => 'interview-result#change'];
                },
                'expanded' => true, // This renders the options as radio buttons
                'multiple' => false, // This ensures only one option can be selected
            ])
            ->add('location', TextType::class, [
                'attr' => ['class' => 'form-control nex_round_hide'],
                'required' => false,
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control nex_round_hide'],
                'required' => false,
            ])
            ->add('comments', TextType::class, [
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobApplicationInterviewRound::class,
        ]);
    }
}
