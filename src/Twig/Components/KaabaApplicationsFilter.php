<?php
// src/Twig/Components/KaabaApplicationsFilter.php

namespace App\Twig\Components;

use App\Form\KaabaApplicationsFilterType;
use App\Repository\KaabaInstituteRepository;
use App\Repository\KaabaCourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('kaaba_applications_filter')]
class KaabaApplicationsFilter extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true)] public ?int $status = null;
    #[LiveProp(writable: true)] public ?int $scholarship = null;
    #[LiveProp(writable: true)] public ?int $institute = null;
    #[LiveProp(writable: true)] public ?int $course = null;
    #[LiveProp(writable: true)] public ?string $phone = null;
    #[LiveProp(writable: true)] public ?int $region = null;
    #[LiveProp(writable: true)] public ?int $district = null;
    #[LiveProp(writable: true)] public ?int $qualification = null;
    #[LiveProp(writable: true)] public ?int $gender = null;
    #[LiveProp(writable: true)] public ?string $from_date = null;
    #[LiveProp(writable: true)] public ?string $to_date = null;
    #[LiveProp(writable: true)] public int $limit = 100;

    public function __construct(
        private KaabaInstituteRepository $instituteRepo,
        private KaabaCourseRepository $courseRepo,
    ) {}

    protected function instantiateForm(): FormInterface
    {
        $user = $this->getUser();

        // Institutes filtered by user role
        if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $institutes = $this->instituteRepo->findBy(['manager' => $user]);
        } else {
            $institutes = $this->instituteRepo->findAll();
        }

        // Load dependent courses based on selected institute
        $courses = $this->institute
            ? $this->courseRepo->findBy(['institute' => $this->institute])
            : [];

        $formData = [
            'status' => $this->status,
            'scholarship' => $this->scholarship,
            'institute' => $this->institute,
            'course' => $this->course,
            'phone' => $this->phone,
            'region' => $this->region,
            'district' => $this->district,
            'qualification' => $this->qualification,
            'gender' => $this->gender,
            'from_date' => $this->from_date ? new \DateTime($this->from_date) : null,
            'to_date' => $this->to_date ? new \DateTime($this->to_date) : null,
            'limit' => $this->limit,
        ];

        return $this->createForm(KaabaApplicationsFilterType::class, $formData, [
            'user' => $user,
            'institutes' => $institutes,
            'courses' => $courses,
        ]);
    }

    #[LiveAction]
    public function applyFilters(): void
    {
        // The form will automatically sync with the LiveProp properties
        // When properties change, the component will re-render
        // You can add additional logic here if needed
    }
}