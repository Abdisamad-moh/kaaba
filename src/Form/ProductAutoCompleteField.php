<?php
    namespace App\Form;

use App\Entity\MetierPackages;
use App\Entity\User;
use App\Repository\MetierPackagesRepository;
use App\Repository\UserRepository;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
    use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
    
    #[AsEntityAutocompleteField]
    class ProductAutoCompleteField extends AbstractType
    {
        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'class' => MetierPackages::class,
                'placeholder' => 'Choose Item',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-control form-select'
                ],
                'searchable_fields' => ['name'],
                'query_builder' => function (MetierPackagesRepository $repo) {
                    return $repo->createQueryBuilder('u')
                        ->where('u.status = :status')
                        ->setParameter('status', 1);
                },
            ]);
        }
    
        public function getParent(): string
        {
            return BaseEntityAutocompleteType::class;
        }
    }
    
?>