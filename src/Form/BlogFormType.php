<?php

namespace App\Form;

use App\Entity\MetierBlog;
use App\Entity\MetierBlogCategory;
use Symfony\Component\Form\AbstractType;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Leapt\FroalaEditorBundle\Form\Type\FroalaEditorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BlogFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'required'=> true,
            ])
            ->add('subtitle', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('date', null, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'data' => new \DateTime('now'),
            ])
            // ->add('description', FroalaEditorType::class, [
            //     'froala_toolbarButtons' => [
            //         'bold',
            //         'italic',
            //         'underline',
            //         'fontSize',         // Allow users to select font size
            //         'paragraphFormat',  // Allow users to select headings (e.g., H1, H2)
            //     ],
            //     'froala_height' => 300,
            //     'froala_fontSizeSelection' => ['8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30'],  // Customize font sizes
            //     'froala_paragraphFormatSelection' => [
            //         'N' => 'Normal',
            //         'H1' => 'Heading 1',
            //         'H2' => 'Heading 2',
            //         'H3' => 'Heading 3',
            //         'H4' => 'Heading 4'
            //     ], // Customize heading options
            //     'froala_saveURL' => null,
            //     // Make sure auto-save is not enabled
            //     'froala_saveInterval' => 0, // Ensure auto-save interval is set to 0
            //     'froala_saveMethod' => null,
            //     'froala_saveParams' => null,
            //     'froala_saveURLParams' => null,
            // ])
            ->add('description', CKEditorType::class, [
                'attr' => ['class' => 'form-control required '],
                'config' => [
                    'toolbar' => 'basic', // You can specify toolbar configuration here
                ],
            ])
           
            ->add('image', FileType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'required'=> false,
                'data' => null,
            ])
            ->add('blog_type', ChoiceType::class, [
                'choices' => [
                    'Choose Type' => null,
                    'Employer' => true,
                    'Job Seeker' => false,
                ],
                'required'=> true,
                'attr' => [
                    'class' => 'form-select',
                ]
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => true,
                    'In-Active' => false,
                ],
                'required'=> true,
                'attr' => [
                    'class' => 'form-select',
                ]
            ])
            // ->add('createdAt', null, [
            //     'widget' => 'single_text',
            // ])
            ->add('category', EntityType::class, [
                'class' => MetierBlogCategory::class,
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MetierBlog::class,
        ]);
    }
}
