<?php

namespace App\Form;

use App\Entity\KaabaApplicationStatus;
use App\Entity\KaabaScholarship;
use App\Entity\KaabaInstitute;
use App\Entity\KaabaCourse;
use App\Entity\KaabaRegion;
use App\Entity\KaabaDistrict;
use App\Entity\KaabaQualification;
use App\Entity\KaabaGender;

use App\Repository\KaabaCourseRepository;
use App\Repository\KaabaInstituteRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KaabaApplicationSearchType extends AbstractType
{
    private KaabaCourseRepository $courseRepo;
    private KaabaInstituteRepository $instituteRepo;

    public function __construct(
        KaabaCourseRepository $courseRepo,
        KaabaInstituteRepository $instituteRepo
    ) {
        $this->courseRepo = $courseRepo;
        $this->instituteRepo = $instituteRepo;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status', EntityType::class, [
                'class' => KaabaApplicationStatus::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Filter by status',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('scholarship', EntityType::class, [
                'class' => KaabaScholarship::class,
                'choice_label' => 'title',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Filter by scholarship',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('institute', EntityType::class, [
                'class' => KaabaInstitute::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'choices' => $options['institutes'],
                'placeholder' => 'Filter by institute',
                'attr' => [
                    'class' => 'form-control institute-select',
                    'col_class' => 'col-md-3'
                ],
            ])
            ->add('course', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select course',
                'choices' => [], // initially empty
                'attr' => [
                    'class' => 'form-control course-select',
                    'col_class' => 'col-md-3'
                ],
            ])
            ->add('from_date', DateType::class, [
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('to_date', DateType::class, [
                'required' => false,
                'mapped' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Phone number',
                    'col_class' => 'col-md-3'
                ]
            ])
            ->add('region', EntityType::class, [
                'class' => KaabaRegion::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select region',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('district', EntityType::class, [
                'class' => KaabaDistrict::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select district',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('qualification', EntityType::class, [
                'class' => KaabaQualification::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select qualification',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('gender', EntityType::class, [
                'class' => KaabaGender::class,
                'choice_label' => 'name',
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select gender',
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-3']
            ])
            ->add('limit', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => ['25' => 25, '50' => 50, '100' => 100, '200' => 200, '500' => 500],
                'attr' => ['class' => 'form-control', 'col_class' => 'col-md-2']
            ]);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {

            $form = $event->getForm();
            $request = $options['request'];

            $instituteId = $request->query->get('institute');
            $courseId    = $request->query->get('course');

            if (!$instituteId) return;

            $courses = $this->courseRepo->findBy(['institute' => $instituteId]);

            $form->add('course', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select course',
                'choices' => $courses,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'data' => $courseId ? $this->courseRepo->find($courseId) : null,
                'attr' => ['class' => 'form-control course-select', 'col_class' => 'col-md-3'],
            ]);
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

            $data = $event->getData();
            $form = $event->getForm();

            $instituteId = $data['institute'] ?? null;

            if (!$instituteId) return;

            $courses = $this->courseRepo->findBy(['institute' => $instituteId]);

            $form->add('course', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'placeholder' => 'Select course',
                'choices' => $courses,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'attr' => ['class' => 'form-control course-select', 'col_class' => 'col-md-3'],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'institutes' => [],
            'request' => null,
        ]);
    }
}
