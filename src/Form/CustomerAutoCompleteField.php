<?php 
namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CustomerAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => User::class,
            'placeholder' => 'Choose a customer',
            'choice_label' => 'name',
            'attr' => [
                'class' => 'form-control form-select'
            ],
            'searchable_fields' => ['name'],
            'status' => 1,
            'type' => 'jobseeker', // default, can be overridden from form
            'query_builder' => function (UserRepository $repo, array $options) {
                return $repo->createQueryBuilder('u')
                    ->where('u.type = :type')
                    ->andWhere('u.status = :status')
                    ->setParameter('type', $options['type'])
                    ->setParameter('status', $options['status']);
            },
        ]);

        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedTypes('status', ['int']);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
