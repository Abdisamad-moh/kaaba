<?php

namespace App\Form;

use App\Entity\InterviewQuestionJobTitle;
use App\Repository\InterviewQuestionJobTitleRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class QaTitleAutocomplete extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => InterviewQuestionJobTitle::class,
            'placeholder' => 'Select Job Title',
            'choice_label' => 'name',
            'required' => false,
            'searchable_fields' => ['name'],
            'query_builder' => function (InterviewQuestionJobTitleRepository $repo) {
                return $repo->createQueryBuilder('c');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}