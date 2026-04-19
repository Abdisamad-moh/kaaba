<?php
// src/Form/ExcuseConfirmationType.php

namespace App\Form;

use App\Entity\KaabaStudentExcuse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExcuseConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('is_approved', CheckboxType::class, [
                'required' => false,
                'label' => 'Approve this excuse',
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('admin_notes', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Add confirmation notes (optional)'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KaabaStudentExcuse::class,
        ]);
    }
}