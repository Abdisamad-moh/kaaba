<?php 
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OtpVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        for ($i = 1; $i <= $options['otp_length']; $i++) {
            $builder->add('digit' . $i, TextType::class, [
                'label' => false,
                'attr' => [
                    'maxlength' => 1,
                    'pattern' => '[0-9]',
                    'inputmode' => 'numeric',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'otp_length' => 6, // Default OTP length
        ]);
    }
}
?>