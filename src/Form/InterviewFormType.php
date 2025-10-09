<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierCity;
use App\Entity\MetierCountry;
use App\Entity\JobApplication;
use App\Entity\JobApplicationInterview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\DataTransformer\CityToNameTransformer;
use App\Form\DataTransformer\StateToNameTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class InterviewFormType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('which_round', NumberType::class, [
                'mapped' => false,
                'required' => true,
                'data' => 0,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('notes')
            ->add('type', HiddenType::class, [
                'data' => 'in person'
            ])
            ->add('meeting_link')
            ->add('location',TextType::class, [
                'required' => true,
            ])
            ->add('rounds')
          
            ->add('country', CountryAutoCompleteField::class, [
                'choice_label' => 'name',
                'required' => true,
                'attr' => [
                    'class' => 'form-select country_input'
                ],
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-select city_input'
                ],
            ])
            
        ;

        $builder->get('city')->addModelTransformer(new CityToNameTransformer($this->em));
        // $builder->get('state')->addModelTransformer(new StateToNameTransformer($this->em));

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobApplicationInterview::class,
        ]);
    }
}
