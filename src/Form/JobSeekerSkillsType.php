<?php

namespace App\Form;

use App\Entity\EmployerDetails;
use App\Entity\JobseekerDetails;
use App\Entity\MetierSkills;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSeekerSkillsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobSeekerSkills', EntityType::class, [
                'class' => MetierSkills::class,
                'choice_label' => 'name',
                'multiple' => true,
                'autocomplete' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
