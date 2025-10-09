<?php

namespace App\Form;

use App\Entity\JobSeekerWork;
use App\Entity\MetierCareers;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSeekerWorkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('experience', ChoiceType::class, [
                'choices' => [
                    'Entry-level (0-2 years)' => 0,
                    'Intermediate or Mid-level (3-5 years)' => 1,
                    'Senior-level (6-8 years)' => 2,
                    'Managerial-level (9-12 years)' => 3,
                    'Director-level (13-15 years)' => 4,
                    'Executive-level (16+ years)' => 5,
                ],
                'required' => true
            ])
            ->add('salary')
            ->add('profession', CareerAutoCompleteField::class, [
                'class' => MetierCareers::class,
                'choice_label' => 'name',
                'required' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerWork::class,
        ]);
    }
}
