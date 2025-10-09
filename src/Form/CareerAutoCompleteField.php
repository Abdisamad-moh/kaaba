<?php

namespace App\Form;

use App\Entity\MetierCity;
use App\Entity\MetierCareers;
use App\Repository\CareersRepository;
use App\Repository\MetierCityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CareerAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => MetierCareers::class,
            'placeholder' => 'Choose a Career',
            'choice_label' => 'name',
            'attr' => [
                'class' => 'form-control form-select'
            ],
            // choose which fields to use in the search
            // if not passed, *all* fields are used
            'searchable_fields' => ['name'],
            'query_builder' => function(CareersRepository $repo) {
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
