<?php

namespace App\Form;

use App\Entity\JobseekerDetails;
use App\Entity\MetierCareers;
use App\Entity\MetierCity;
use App\Entity\MetierCountry;
use App\Entity\MetierGender;
use App\Entity\MetierState;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobSeekerCvType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cv', FileType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobseekerDetails::class,
        ]);
    }
}
