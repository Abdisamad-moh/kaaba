<?php

namespace App\Form;

use App\Entity\KaabaApplication;
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

class RejectFormType extends AbstractType
{
    public function __construct(private Security $security)
    {

    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rejection_reason', TextareaType::class, [
                'attr' => ['class' => 'form-control form-control-lg', 'rows' => 12],
                'required' => true

        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KaabaApplication::class,
        ]);
    }
}
