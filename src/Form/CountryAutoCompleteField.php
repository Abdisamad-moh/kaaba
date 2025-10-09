<?php

namespace App\Form;

use App\Entity\MetierCity;
use App\Entity\MetierCountry;
use App\Repository\MetierCityRepository;
use App\Repository\MetierCountryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CountryAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => MetierCountry::class,
            'placeholder' => 'Select Country',
            'choice_label' => 'name',
            'required' => false,
            // choose which fields to use in the search
            // if not passed, *all* fields are used
            'searchable_fields' => ['name'],
            'query_builder' => function(MetierCountryRepository $repo) {
                return $repo->createQueryBuilder('c')
                ;
            },
            // 'security' => 'ROLE_SOMETHING',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}