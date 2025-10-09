<?php

namespace App\Form;

use App\Entity\JobSeekerLanguage;
use App\Entity\MetierLanguage;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSeekerLanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('proficiency', ChoiceType::class, [
                'choices' => [
                    'Basic' => 'basic',
                    'Intermediate' => 'intermediate',
                    'Proficient' => 'proficient',
                    'Native' => 'native',
                ],
                'autocomplete' => true,
                'placeholder' => 'Proficiency'
            ])
            ->add('reading', CheckboxType::class, [
                'label' => 'Read',
                'required' => false,
            ])
            ->add('writing', CheckboxType::class, [
                'label' => 'Write',
                'required' => false,
            ])
            ->add('speaking', CheckboxType::class, [
                'label' => 'Speak',
                'required' => false,
            ])
            ->add('language', EntityType::class, [
                'class' => MetierLanguage::class,
                'choice_label' => 'name',
                'autocomplete' => true,
                'placeholder' => 'Language'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerLanguage::class,
        ]);
    }
}
