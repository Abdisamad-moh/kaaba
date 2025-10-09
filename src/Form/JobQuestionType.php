<?php

namespace App\Form;

use App\Entity\EmployerJobs;
use App\Entity\EmployerJobQuestion;
use App\Form\JobQuestionAnswerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;

class JobQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('employerJobQuestionAnswers', LiveCollectionType::class, [
                'entry_type' => JobQuestionAnswerType::class,
                'entry_options' => ['label' => false],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                // 'disabled' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployerJobQuestion::class,
        ]);
    }
}
