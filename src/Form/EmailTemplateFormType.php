<?php

namespace App\Form;

use App\Entity\MetierEmailTemps;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Leapt\FroalaEditorBundle\Form\Type\FroalaEditorType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EmailTemplateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', FroalaEditorType::class, [
                'froala_toolbarButtons' => [
                    'bold',
                    'italic',
                    'underline',
                    'fontSize',         // Allow users to select font size
                    'paragraphFormat',  // Allow users to select headings (e.g., H1, H2)
                ],
                'froala_height' => 300,
                'froala_fontSizeSelection' => ['8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30'],  // Customize font sizes
                'froala_paragraphFormatSelection' => [
                    'N' => 'Normal',
                    'H1' => 'Heading 1',
                    'H2' => 'Heading 2',
                    'H3' => 'Heading 3',
                    'H4' => 'Heading 4'
                ], // Customize heading options
                'froala_saveURL' => null,
                // Make sure auto-save is not enabled
                'froala_saveInterval' => 0, // Ensure auto-save interval is set to 0
                'froala_saveMethod' => null,
                'froala_saveParams' => null,
                'froala_saveURLParams' => null,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Choose Type' => null,
                    'Employer' => 'employer',
                    'Job Seeker' => 'jobseeker',
                    'General' => 'general',
                ],
                'required'=> true,
                'attr' => [
                    'class' => 'form-select form-select-lg',
                ]
            ])
            ->add('action', TextType::class, [
               
                'required'=> true,
                'attr' => [
                    'class' => 'form-control-lg form-control',
                ]
            ])
            ->add('subject', TextType::class, [
                'attr' => ["class"=> 'form-control-lg form-control',],
                'required'=> true,
            ])
            ->add('header', TextType::class, [
                'attr' => ["class"=> 'form-control-lg form-control',],
                'required'=> true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierEmailTemps::class,
        ]);
    }
}
