<?php

namespace App\Form;

use App\Entity\EmployerJobQuestion;
use Symfony\Component\Form\AbstractType;
use App\Entity\EmployerJobQuestionAnswer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JobQuestionAnswerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            // ->add('description')
            ->add('is_right', ChoiceType::class, [
                'label' => 'Is Right?',
                'choices' => [
                    'Is right' => null,
                    'Yes' => true,
                    'No' => false,
                ],
                // 'choices_as_values' => true, // Important for proper data handling
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployerJobQuestionAnswer::class,
        ]);
    }
}
