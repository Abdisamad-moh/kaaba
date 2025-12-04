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
            'placeholder' => 'Search by applicant id, name or phone',
            'choice_label' => function (KaabaApplication $customer) {
                return sprintf('%s - %s - %s', 
                    str_pad($customer->getId(), 5, '0', STR_PAD_LEFT), 
                    $customer->getFullName(), 
                    $customer->getPhone()
                );
            },
            'required' => false,
            'searchable_fields' => ['id', 'full_name', 'phone'],
            'query_builder' => function (KaabaApplicationRepository $repo) {
                return $repo->createQueryBuilder('c');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
