<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierSkills;
use App\Entity\MetierCareers;
use App\Entity\JobSeekerResume;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class JobSeekerPsReportFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('s_report', FileType::class, [
                'required' => false,
                'data_class' => null,
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new File([
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid PDF document.',
                        'maxSize' => '2024k',
                        'maxSizeMessage' => 'The maximum allowed file size is 2MB.',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobSeekerResume::class,
        ]);
    }
}
