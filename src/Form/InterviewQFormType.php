<?php

namespace App\Form;

use App\Entity\InterviewQuestions;
use Symfony\Component\Form\AbstractType;
use App\Form\QuestionsJobTypesAutocompleteField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class InterviewQFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                ],
                'required'=> true,
            ])
            ->add('a', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                ],
                'required'=> true,
            ])
            ->add('r', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control form-control-lg',
                ],
                'required'=> true,
            ])
            ->add('job_type', QuestionsJobTypesAutocompleteField::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InterviewQuestions::class,
        ]);
    }
}
