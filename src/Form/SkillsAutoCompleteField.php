<?php

namespace App\Form;

use App\Entity\MetierCity;
use App\Entity\MetierSkills;
use App\Entity\MetierCareers;
use App\Repository\SkillsRepository;
use App\Repository\CareersRepository;
use App\Repository\MetierCityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class SkillsAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => MetierSkills::class,
            'placeholder' => 'Choose a Skill',
            'autocomplete' => true,
            'choice_label' => 'name',
            'multiple' => true,
            'attr' => [
                'class' => 'form-control form-select'
            ],
            // choose which fields to use in the search
            // if not passed, *all* fields are used
            'searchable_fields' => ['name'],
            'query_builder' => function(SkillsRepository $repo) {
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
