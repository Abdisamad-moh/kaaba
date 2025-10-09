<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierContacts;
use Symfony\Component\Form\AbstractType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactFormType extends AbstractType
{
    public function __construct(private Security $security)
    {

    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', ChoiceType::class, [
                'choices' => [
                    'Suggestion' => 'Suggestion',
                    'Question' => 'Question',
                    'Other' => 'Other',
                ],
            'placeholder' => 'Please select a subject', // Add this line
            'invalid_message' => 'Please select a subject from the list',
            'required' => true,
            'attr' => [
                'class' => 'form-select form-select-lg',
            ],
        ])
            ->add('message', TextareaType::class, [
                'attr' => ['class' => 'form-control form-control-lg', 'rows' => 5],
            'required' => true

        ]);
            
            if(!$this->security->getUser())
                $builder->add('email', EmailType::class, [
                    'label' => 'Email',
                    'attr' => ['class' => 'form-control form-control-lg'],
                    'required' => true
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierContacts::class,
        ]);
    }
}
