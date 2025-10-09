<?php

namespace App\Form;

use DateTime;
use App\Entity\User;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\NotBlank as ConstraintsNotBlank;

class RegistrationJobseekerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'First Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a first name',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Your first name should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 255,
                    ]),
                ],
                
            ])
            ->add('middleName', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Middle Name',
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Last Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a last name',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Your last name should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Email ID',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-check-input'
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                // 'type' => PasswordType::class,
                'invalid_message' => 'Passwords must match.',
                // Optional: Set a different label for the confirm field
                'label' => 'Confirm Password',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                
            ])
            ->add('dob', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date of Birth',
                'data' => new DateTime(),
                'mapped' => false,
                'constraints' => [
                    new NotBlank(), // Ensure the field is not empty,
                    new Callback(function ($birthdate, ExecutionContextInterface $context) {
                        if ($birthdate) {
                            $eighteenYearsAgo = (new DateTime())->modify('-18 years');
                            if ($birthdate > $eighteenYearsAgo) {
                                $context->buildViolation('You must be 18 years old or older to register.')
                                    ->atPath('dob') // This targets the 'dob' field
                                    ->addViolation();
                            }
                        }
                    }),

                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            // ->add('captcha', Recaptcha3Type::class, [
            //     'constraints' => new Recaptcha3(),
            //     'action_name' => 'jobseekerRegistration',
            //     'required' => true

            // ])

        ;
        // $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
        //     $form = $event->getForm();
        //     $birthdate = $form->get('dob')->getData();

        //     if ($birthdate) {
        //         $eighteenYearsAgo = (new DateTime())->modify('-18 years');
        //         if ($birthdate > $eighteenYearsAgo) {
        //             $form->addError(new FormError('You must be 18 years old or older to register.'));
        //         }
        //     }
        // });
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $plainPassword = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            // Perform custom password validation logic
            if ($plainPassword !== $confirmPassword) {
                $form->get('confirmPassword')->addError(new FormError('Passwords must match.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
