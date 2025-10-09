<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\MetierCity;
use App\Entity\MetierState;
use App\Entity\MetierGender;
use App\Entity\MetierSkills;
use App\Entity\MetierCareers;
use App\Entity\MetierCountry;
use App\Entity\JobseekerDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class JobSeekerDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dob', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'mapped' => false,
                'data' => $options['user']->getName(),
            ])
            ->add('phone', TextType::class, [
                'required' => true,
            ])
            ->add('whatsappPhone')
            ->add('location')
            ->add('country', CountryAutoCompleteField::class)
            ->add('city', CityAutocompleteField::class)
            ->add('gender', EntityType::class, [
                'class' => MetierGender::class,
                'choice_label' => 'name',
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'mapped' => false,
                'data' => $options['user']->getEmail(),
            ])
            ->add('profilePictureFile', FileType::class, [
                'label' => 'Profile Picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF).',
                    ]),
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobseekerDetails::class,
            'user' => null,
        ]);

        $resolver->setRequired('user');
    }
}
