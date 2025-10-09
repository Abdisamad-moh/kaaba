<?php
    namespace App\Form;

use App\Entity\EmployerJobs;
use App\Entity\User;
use App\Repository\EmployerJobsRepository;
use App\Repository\UserRepository;
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\OptionsResolver\OptionsResolver;
    use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
    use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
    
    #[AsEntityAutocompleteField]
    class JobFieldCompleteField extends AbstractType
    {
        public function configureOptions(OptionsResolver $resolver): void
        {
            $resolver->setDefaults([
                'class' => EmployerJobs::class,
                'placeholder' => 'Choose an Job Title',
                'choice_label' => 'title',
                'attr' => [
                    'class' => 'form-control form-select'
                ],
                'searchable_fields' => ['name','id'],
                'query_builder' => function (EmployerJobsRepository $repo) {
                    return $repo->createQueryBuilder('u');
                },
            ]);
        }
    
        public function getParent(): string
        {
            return BaseEntityAutocompleteType::class;
        }
    }
    
?>