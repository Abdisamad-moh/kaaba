<?php

namespace App\Form;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QaFormType extends AbstractType
{
    public function __construct(private EntityManagerInterface $em)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

        ->add('title', QaTitleAutocomplete::class, [
            'attr' => [
                'class' => 'form-select'
            ],
            'required' => true,
        ]);

     
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => EmployerJobs::class,
        ]);
    }
}
