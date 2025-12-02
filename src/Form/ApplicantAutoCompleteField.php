<?php

namespace App\Form;

use App\Entity\KaabaApplication;
use App\Entity\MetierCity;
use App\Repository\KaabaApplicationRepository;
use App\Repository\MetierCityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class ApplicantAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => KaabaApplication::class,
            'placeholder' => 'Find Applicant by Name or Phone',
            'choice_label' => function (KaabaApplication $customer) {
                return sprintf('%s - %s', $customer->getFullName(), $customer->getPhone());
            },
            'required' => false,
            // choose which fields to use in the search
            // if not passed, *all* fields are used
            'searchable_fields' => ['full_name', 'phone'],
            'query_builder' => function (KaabaApplicationRepository $repo) {
                return $repo->createQueryBuilder('c');
            },
            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
