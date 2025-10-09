<?php
    namespace App\Form;

    use App\Entity\User;
    use App\Repository\UserRepository;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
    use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
    
    #[AsEntityAutocompleteField]
    class JobseekerAutoCompleteField extends AbstractType
    {
        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'class' => User::class,
                'placeholder' => 'Choose an Jobseeker',
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-control form-select'
                ],
                'searchable_fields' => ['name'],
                'query_builder' => function (UserRepository $repo) {
                    return $repo->createQueryBuilder('u')
                        ->where('u.type = :type')
                        ->andWhere('u.status = :status')
                        ->setParameter('type', 'jobseeker')
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