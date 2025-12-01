<?php

namespace App\Form;

use App\Entity\KaabaApplicationExam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class KaabaApplicationExamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('examResult', ChoiceType::class, [
                'choices' => [
                    'Choose Result' => null,
                    'Passed' => 'passed',
                    'Failed' => 'failed',
                ],
                'required' => true,
                'label' => 'Exam Result'
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description (optional)'
            ])
            ->add('attachment', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Upload Exam Script / File'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KaabaApplicationExam::class,
        ]);
    }
}
