<?php
    namespace App\Form;

    use App\Entity\User;
    use App\Repository\UserRepository;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
    use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
    
    #[AsEntityAutocompleteField]
    class CustomerAutoComplete extends AbstractType
    {
        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'class' => User::class,
                'placeholder' => 'Choose an Customer',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-control form-select'
                ],
                'searchable_fields' => ['name','email'],
                'query_builder' => function (UserRepository $repo) {
                    return $repo->createQueryBuilder('u')
                        // ->where('u.type = :type')
                        ->where('u.status = :status')
                        // ->setParameter('type', 'employer')
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