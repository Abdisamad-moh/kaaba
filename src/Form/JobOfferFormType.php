<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\JobOffering;
use App\Entity\JobApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class JobOfferFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salary')
            ->add('joining_date', null, [
                'widget' => 'single_text',
            ])
            ->add('probation_period')
            ->add('offer_letter', FileType::class, [
                'required' => false,
                'data_class' => null,
            ])
            
         
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobOffering::class,
        ]);
    }
}
