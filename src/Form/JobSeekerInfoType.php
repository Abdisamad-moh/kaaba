<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierCity;
use App\Entity\MetierState;
use App\Entity\MetierGender;
use App\Entity\MetierCountry;
use App\Entity\JobseekerDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class JobSeekerInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dob', null, [
                'widget' => 'single_text',
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'mapped' => false,
            ])
            ->add('phone')
            ->add('whatsappPhone')
            ->add('location')
            ->add('country', CountryAutoCompleteField::class)
            ->add('city', CityAutocompleteField::class)
            ->add('gender', EntityType::class, [
                'class' => MetierGender::class,
                'choice_label' => 'id',
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'mapped' => false,
            ])
            ->add('state', StateAutoCompleteField::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobseekerDetails::class,
        ]);
    }
}
