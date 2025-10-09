<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;

class RegistrationEmployerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Company Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please provide a company name',
                    ]),
                ],
            ])
            ->add('email',EmailType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Company Email ID',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email address',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
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
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control '
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                // 'type' => PasswordType::class,
                'invalid_message' => 'Passwords must match.',
                // Optional: Set a different label for the confirm field
                'label' => 'Confirm Password',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control '
                ],
              ])
              ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'jobseekerRegistration',
                'required' => false

            ])
        ;

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
