<?php

namespace App\Form;

use App\Entity\MetierCity;
use App\Entity\MetierState;
use App\Entity\EmployerJobs;
use App\Entity\MetierSkills;
use App\Entity\MetierBenefit;
use App\Entity\MetierCareers;
use App\Entity\MetierCountry;
use App\Entity\MetierJobType;
use App\Entity\MetierCurrency;
use App\Entity\MetierJobCategory;
use App\Entity\MetierJobIndustry;
use App\Entity\MetierWorkShift;
use App\Form\StatesAutoCompleteField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\UX\LiveComponent\Form\Type\LiveCollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class JobFormTypeShort extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('employerJobQuestions', LiveCollectionType::class, [
                'entry_type' => JobQuestionType::class,
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
            'data_class' => EmployerJobs::class,
        ]);
    }
}
