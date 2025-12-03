<?php

namespace App\Form;

use App\Entity\KaabaAssessment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class KaabaAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var KaabaAssessment|null $assessment */
        $assessment = $options['data'];

        // Pull stored JSON values from entity (for edit mode)
        $motivation = $assessment?->getMotivation() ?? [];
        $household = $assessment?->getHousehold() ?? [];
        $income = $assessment?->getIncome() ?? [];

        /** ----------------------------------------------------
         * SECTION B — MOTIVATION
         ----------------------------------------------------- */
        $builder

            ->add('interviewerName', TextType::class, [
                'label' => 'Interviewer',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('employment_status', ChoiceType::class, [
                'label' => 'Employment Status',
                'mapped' => false,
                'data' => $motivation['employment_status'] ?? null,
                'choices' => [
                    'Full-time Employed' => 1,
                    'Part-time Employed' => 2,
                    'Self-employed' => 3,
                    'Unemployed' => 4,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'motivation'],
            ])
            ->add('training_enrolment', ChoiceType::class, [
                'label' => 'Currently Enrolled in Training',
                'mapped' => false,
                'data' => $motivation['training_enrolment'] ?? null,
                'choices' => [
                    'Yes' => 0,
                    'No' => 4,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'motivation'],
            ])
            ->add('reason_for_applying', ChoiceType::class, [
                'label' => 'Reason for Applying',
                'mapped' => false,
                'data' => $motivation['reason_for_applying'] ?? null,
                'choices' => [
                    'Use skill at work' => 1,
                    'Career goal alignment' => 2,
                    'Help family / improve future' => 3,
                    'To get a job' => 4,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'motivation'],
            ])
            ->add('career_aspirations', ChoiceType::class, [
                'label' => 'Career Aspirations',
                'mapped' => false,
                'data' => $motivation['career_aspirations'] ?? null,
                'choices' => [
                    'Global Sector Opportunity' => 1,
                    'Entrepreneurship / Self-Employment' => 2,
                    'Further Education Specialisation' => 3,
                    'Employment and Professional Growth' => 4,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'motivation'],
            ])
            ->add('community_participation', ChoiceType::class, [
                'label' => 'Community, Leadership or Talent Participation',
                'mapped' => false,
                'data' => $motivation['community_participation'] ?? null,
                'choices' => [
                    'None but interested' => 1,
                    'Special Talent Programme' => 2,
                    'Community Programme' => 3,
                    'Leadership Programme' => 4,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'motivation'],
            ]);


        /** ----------------------------------------------------
         * SECTION C — HOUSEHOLD
         ----------------------------------------------------- */
        $builder
            ->add('housing_situation', ChoiceType::class, [
                'label' => 'Housing Situation',
                'mapped' => false,
                'data' => $household['housing_situation'] ?? null,
                'choices' => [
                    'Owned' => 1,
                    'Rented' => 2,
                    'Shared' => 3,
                    'Temporary housing' => 4,
                    'Boarding' => 5,
                    'IDP' => 6,
                    'Slum housing' => 7,
                    'Homeless' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'household'],
            ])
            ->add('household_members', ChoiceType::class, [
                'label' => 'Number of Household Members',
                'mapped' => false,
                'data' => $household['household_members'] ?? null,
                'choices' => [
                    'Less than 2' => 1,
                    '2' => 2,
                    '3–5' => 3,
                    '6' => 4,
                    '7–9' => 5,
                    '10' => 6,
                    '11–12' => 7,
                    'More than 12' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'household'],
            ])
            ->add('decision_maker', ChoiceType::class, [
                'label' => 'Primary Decision Maker',
                'mapped' => false,
                'data' => $household['decision_maker'] ?? null,
                'choices' => [
                    'Father' => 1,
                    'Mother' => 2,
                    'Both Parents (joint)' => 3,
                    'Guardian' => 4,
                    'Distant relative' => 5,
                    'Breadwinner' => 6,
                    'Collective' => 7,
                    'Sibling / Self' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'household'],
            ])
            ->add('dependency_ratio', ChoiceType::class, [
                'label' => 'Dependency Ratio',
                'mapped' => false,
                'data' => $household['dependency_ratio'] ?? null,
                'choices' => [
                    'No dependents' => 1,
                    'Children under 18' => 3,
                    'Elderly only' => 5,
                    'Mixed dependents' => 7,
                    'Special circumstances' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'household'],
            ])
            ->add('household_circumstances', ChoiceType::class, [
                'label' => 'Special Household Circumstances',
                'mapped' => false,
                'data' => $household['household_circumstances'] ?? null,
                'choices' => [
                    'Drought impacted' => 2,
                    'Minority group' => 3,
                    'Female headed household' => 4,
                    'Illness in household' => 6,
                    'Other circumstances' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'household'],
            ]);


        /** ----------------------------------------------------
         * SECTION D — INCOME
         ----------------------------------------------------- */
        $builder
            ->add('breadwinner_occupation', ChoiceType::class, [
                'label' => 'Breadwinner',
                'mapped' => false,
                'data' => $income['breadwinner_occupation'] ?? null,
                'choices' => [
                    'Parent' => 2,
                    'Guardian' => 4,
                    'Myself' => 6,
                    'Other relative' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'income'],
            ])
            ->add('income_sources', ChoiceType::class, [
                'label' => 'Primary Income Source',
                'mapped' => false,
                'data' => $income['income_sources'] ?? null,
                'choices' => [
                    'Job / Salaries' => 1,
                    'Small Business' => 2,
                    'Pastoral / Agricultural' => 4,
                    'Remittance' => 6,
                    'Aid / Grant' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'income'],
            ])
            ->add('monthly_income', ChoiceType::class, [
                'label' => 'Monthly Household Income',
                'mapped' => false,
                'data' => $income['monthly_income'] ?? null,
                'choices' => [
                    'More than $2000' => 1,
                    '$1500 – $2000' => 2,
                    '$1200 – $1500' => 3,
                    '$1000 – $1200' => 4,
                    '$500 – $1000' => 5,
                    '$300 – $500' => 6,
                    '$150 – $300' => 7,
                    '$65 – $150' => 8,
                    'Less than $65' => 9,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'income'],
            ])
            ->add('household_savings', ChoiceType::class, [
                'label' => 'Household Savings',
                'mapped' => false,
                'data' => $income['household_savings'] ?? null,
                'choices' => [
                    'Formal Savings' => 1,
                    'Mobile Savings' => 2,
                    'Community Savings' => 3,
                    'Cash Savings' => 4,
                    'Investments' => 6,
                    'Inheritance' => 7,
                    'No savings' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'income'],
            ])
            ->add('financial_vulnerability', ChoiceType::class, [
                'label' => 'Financial Vulnerability',
                'mapped' => false,
                'data' => $income['financial_vulnerability'] ?? null,
                'choices' => [
                    'Debt' => 2,
                    'Irregular income' => 4,
                    'Reliance on aid' => 6,
                    'Other stress' => 8,
                ],
                'placeholder' => 'Select...',
                'attr' => ['class' => 'form-control', 'data-section' => 'income'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => KaabaAssessment::class,
        ]);
    }
}
