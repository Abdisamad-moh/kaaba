<?php

namespace App\Controller;

use App\Service\BioTimeService;
use App\Entity\KaabaBiotimeArea;
use App\Entity\KaabaBiotimeDevice;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\KaabaCourseRepository;
use App\Service\BioTimeIntegrationService;
use App\Repository\KaabaInstituteRepository;
use App\Repository\KaabaAttendanceRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\KaabaBiotimeAreaRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\KaabaBiotimeDeviceRepository;
use App\Repository\KaabaStudentDeviceRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\KaabaConfigSchoolHourRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Repository\KaabaConfigSchoolHolidayRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/biotime')]
class BioTimeController extends AbstractController
{
    // ========== AREAS MANAGEMENT ==========

    #[Route('/areas', name: 'app_admin_biotime_areas')]
    public function areas(
        Request $request,
        KaabaBiotimeAreaRepository $areaRepository,
        KaabaInstituteRepository $instituteRepository,
        KaabaConfigSchoolHourRepository $hoursRepository,
        KaabaConfigSchoolHolidayRepository $holidayRepository,
        BioTimeIntegrationService $integrationService,
        EntityManagerInterface $em
    ): Response {
        // Fetch all areas
        $areas = $areaRepository->findAreasWithInstitute();

        // Check if editing or creating new
        $editId = $request->query->get('edit');
        $showForm = $editId || $request->query->get('create');

        $area = new KaabaBiotimeArea();
        if ($editId) {
            $area = $areaRepository->find($editId);
            if (!$area) {
                throw $this->createNotFoundException('BioTime area not found.');
            }
        }

        // Get data for dropdowns
        $institutes = $instituteRepository->findAll();
        $hoursConfigs = $hoursRepository->findAll();
        $holidayConfigs = $holidayRepository->findAll();

        // Create form
        $form = $this->createFormBuilder($area)
            ->add('area_id', TextType::class, [
                'label' => 'BioTime Area ID',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 1, 2, 3'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Area ID is required.'])
                ]
            ])
            ->add('area_name', TextType::class, [
                'label' => 'Area Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Main Campus, Building A'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Area name is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Area name cannot be longer than 255 characters.'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optional description',
                    'rows' => 3
                ]
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Timezone',
                'choices' => [
                    'Africa/Dar_es_Salaam' => 'Africa/Dar_es_Salaam',
                    'Africa/Nairobi' => 'Africa/Nairobi',
                    'Africa/Kampala' => 'Africa/Kampala',
                    'UTC' => 'UTC'
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Timezone is required.'])
                ]
            ])
            ->add('institute', ChoiceType::class, [
                'label' => 'Institute',
                'choices' => array_reduce($institutes, function ($carry, $institute) {
                    $carry[$institute->getName()] = $institute;
                    return $carry;
                }, []),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => 'Select an institute',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('working_hours_config', ChoiceType::class, [
                'label' => 'Working Hours Configuration',
                'choices' => array_reduce($hoursConfigs, function ($carry, $config) {
                    $carry[$config->getInstitute()->getName() . ' - ' . $config->getHoursPerDay() . ' hours'] = $config;
                    return $carry;
                }, []),
                'choice_label' => 'id',
                'choice_value' => 'id',
                'placeholder' => 'Select working hours config',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('holiday_config', ChoiceType::class, [
                'label' => 'Holiday Configuration',
                'choices' => array_reduce($holidayConfigs, function ($carry, $holiday) {
                    $carry[$holiday->getName() . ' (' . $holiday->getDate()->format('Y-m-d') . ')'] = $holiday;
                    return $carry;
                }, []),
                'choice_label' => 'id',
                'choice_value' => 'id',
                'placeholder' => 'Select holiday config',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('biotime_api_key', TextType::class, [
                'label' => 'BioTime API Key',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optional API key for this area'
                ]
            ])
            ->add('biotime_server_url', TextType::class, [
                'label' => 'BioTime Server URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Optional custom server URL for this area'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($area);
            $em->flush();

            $this->addFlash('success', $editId ? 'Area updated successfully.' : 'Area created successfully.');
            return $this->redirectToRoute('app_admin_biotime_areas');
        }

        // Handle delete request
        $deleteId = $request->query->get('delete');
        if ($deleteId) {
            $areaToDelete = $areaRepository->find($deleteId);
            if ($areaToDelete) {
                $em->remove($areaToDelete);
                $em->flush();

                $this->addFlash('success', 'Area deleted successfully.');
                return $this->redirectToRoute('app_admin_biotime_areas');
            } else {
                $this->addFlash('error', 'Area not found.');
            }
        }

        // Handle sync from BioTime
        $sync = $request->query->get('sync');
        if ($sync) {
            $syncResult = $integrationService->syncAreas();

            if ($syncResult['success']) {
                $this->addFlash('success', $syncResult['message']);
            } else {
                $this->addFlash('error', $syncResult['message']);
            }

            return $this->redirectToRoute('app_admin_biotime_areas');
        }

        return $this->render('admin/biotime/areas.html.twig', [
            'areas' => $areas,
            'form' => $form->createView(),
            'editId' => $editId,
            'showForm' => $showForm,
        ]);
    }

    // ========== DEVICES MANAGEMENT ==========

    #[Route('/devices', name: 'app_admin_biotime_devices')]
    public function devices(
        Request $request,
        KaabaBiotimeDeviceRepository $deviceRepository,
        KaabaBiotimeAreaRepository $areaRepository,
        BioTimeIntegrationService $integrationService,
        EntityManagerInterface $em
    ): Response {
        // Fetch all devices
        $devices = $deviceRepository->findActiveDevices();

        // Check if editing or creating new
        $editId = $request->query->get('edit');
        $showForm = $editId || $request->query->get('create');

        $device = new KaabaBiotimeDevice();
        if ($editId) {
            $device = $deviceRepository->find($editId);
            if (!$device) {
                throw $this->createNotFoundException('Device not found.');
            }
        }

        // Get areas for dropdown
        $areas = $areaRepository->findAll();

        // Create form
        $form = $this->createFormBuilder($device)
            ->add('device_id', TextType::class, [
                'label' => 'Device ID',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., device-001'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Device ID is required.'])
                ]
            ])
            ->add('device_name', TextType::class, [
                'label' => 'Device Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Entrance Biometric Device'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Device name is required.'])
                ]
            ])
            ->add('device_type', ChoiceType::class, [
                'label' => 'Device Type',
                'choices' => [
                    'Biometric' => 'biometric',
                    'Card Reader' => 'card',
                    'Facial Recognition' => 'facial',
                    'Hybrid' => 'hybrid',
                    'Other' => 'other'
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Device type is required.'])
                ]
            ])
            ->add('serial_number', TextType::class, [
                'label' => 'Serial Number',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Device serial number'
                ]
            ])
            ->add('ip_address', TextType::class, [
                'label' => 'IP Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 192.168.1.100'
                ]
            ])
            ->add('port', TextType::class, [
                'label' => 'Port',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., 4370'
                ]
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'e.g., Main Entrance, Room 101'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Maintenance' => 'maintenance'
                ],
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Status is required.'])
                ]
            ])
            ->add('area', ChoiceType::class, [
                'label' => 'Area',
                'choices' => array_reduce($areas, function ($carry, $area) {
                    $carry[$area->getAreaName()] = $area;
                    return $carry;
                }, []),
                'choice_label' => 'id',
                'choice_value' => 'id',
                'placeholder' => 'Select an area',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(['message' => 'Area is required.'])
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Additional notes about the device'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($device);
            $em->flush();

            $this->addFlash('success', $editId ? 'Device updated successfully.' : 'Device created successfully.');
            return $this->redirectToRoute('app_admin_biotime_devices');
        }

        // Handle delete request
        $deleteId = $request->query->get('delete');
        if ($deleteId) {
            $deviceToDelete = $deviceRepository->find($deleteId);
            if ($deviceToDelete) {
                $em->remove($deviceToDelete);
                $em->flush();

                $this->addFlash('success', 'Device deleted successfully.');
                return $this->redirectToRoute('app_admin_biotime_devices');
            } else {
                $this->addFlash('error', 'Device not found.');
            }
        }

        // Handle sync devices
        $sync = $request->query->get('sync');
        if ($sync) {
            $syncResult = $integrationService->syncDevices();

            if ($syncResult['success']) {
                $this->addFlash('success', $syncResult['message']);
            } else {
                $this->addFlash('error', $syncResult['message']);
            }

            return $this->redirectToRoute('app_admin_biotime_devices');
        }

        return $this->render('admin/biotime/devices.html.twig', [
            'devices' => $devices,
            'form' => $form->createView(),
            'editId' => $editId,
            'showForm' => $showForm,
        ]);
    }

    // ========== STUDENT ENROLLMENT ==========

    #[Route('/enrollment', name: 'app_admin_biotime_enrollment')]
    public function enrollment(
        Request $request,
        KaabaBiotimeAreaRepository $areaRepository,
        EntityManagerInterface $em,
        BioTimeIntegrationService $integrationService
    ): Response {
        // Get all areas with institutes
        $areas = $areaRepository->findAreasWithInstitute();

        // Get applications that are accepted (status = 4) but not enrolled
        // In your controller
        $qb = $em->createQueryBuilder();
        $applications = $qb->select('a')
            ->from('App\Entity\KaabaApplication', 'a')
            ->leftJoin('App\Entity\KaabaStudentDevice', 'sd', 'WITH', 'sd.application = a')
            ->where('a.status = :statusId')
            ->andWhere('sd.biotime_employee_id IS NULL')
            ->setParameter('statusId', 4)
            ->orderBy('a.full_name', 'ASC')
            ->getQuery()
            ->getResult();

        // Handle enrollment request
        $enrollApplicationId = $request->query->get('enroll');
        $enrollAreaId = $request->query->get('area');

        if ($enrollApplicationId && $enrollAreaId) {
            $application = $em->getRepository(\App\Entity\KaabaApplication::class)->find($enrollApplicationId);
            $area = $areaRepository->find($enrollAreaId);

            if ($application && $area) {
                $result = $integrationService->enrollStudentInBioTime($application, $area);

                if ($result['success']) {
                    $this->addFlash('success', $result['message']);
                } else {
                    $this->addFlash('error', $result['message']);
                }
            } else {
                $this->addFlash('error', 'Application or area not found.');
            }

            return $this->redirectToRoute('app_admin_biotime_enrollment');
        }

        // Handle bulk enrollment
        $bulkEnroll = $request->query->get('bulk_enroll');
        $bulkAreaId = $request->query->get('bulk_area');

        if ($bulkEnroll && $bulkAreaId) {
            $area = $areaRepository->find($bulkAreaId);

            if ($area) {
                $enrolledCount = 0;
                $errorCount = 0;

                foreach ($applications as $application) {
                    $result = $integrationService->enrollStudentInBioTime($application, $area);

                    if ($result['success']) {
                        $enrolledCount++;
                    } else {
                        $errorCount++;
                    }
                }

                $this->addFlash('success', "Bulk enrollment completed: {$enrolledCount} students enrolled, {$errorCount} errors.");
            } else {
                $this->addFlash('error', 'Area not found.');
            }

            return $this->redirectToRoute('app_admin_biotime_enrollment');
        }

        return $this->render('admin/biotime/enrollment.html.twig', [
            'areas' => $areas,
            'applications' => $applications,
        ]);
    }

    #[Route('/test-biotime-create', name: 'test_biotime_create')]
    public function testBioTimeCreate(BioTimeService $bioTimeService): JsonResponse
    {
        $uniqueCode = 'TEST' . time() . rand(100, 999);

        $testData = [
            'emp_code' => $uniqueCode,
            'first_name' => 'Test',
            'last_name' => 'User',
            'department' => 4,
            'area' => [2], // Use your area ID
            'hire_date' => date('Y-m-d'),
            'app_status' => 0
        ];

        try {
            $response1 = $bioTimeService->createEmployee($testData);

            // Try to create again with same emp_code
            $response2 = $bioTimeService->createEmployee($testData);

            return new JsonResponse([
                'first_create' => $response1,
                'second_create_same_code' => $response2,
                'test_data' => $testData,
                'conclusion' => 'If second create returns same employee ID, BioTime is doing upsert'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

  #[Route('/enrolled-students', name: 'app_admin_biotime_enrolled_students')]
public function enrolledStudents(
    Request $request,
    EntityManagerInterface $em,
    KaabaInstituteRepository $instituteRepository,
    KaabaCourseRepository $courseRepository,
    KaabaStudentDeviceRepository $studentDeviceRepository
): Response {
    // Get filter parameters
    $instituteId = $request->query->get('institute');
    $courseId = $request->query->get('course');
    $employeeCode = $request->query->get('employee_code');
    $enrollmentDateFrom = $request->query->get('enrollment_date_from');
    $enrollmentDateTo = $request->query->get('enrollment_date_to');

    // dd($studentDeviceRepository->findAll());
    // Create query builder
    $qb = $em->createQueryBuilder();
    $qb->select('sd')
        ->from('App\Entity\KaabaStudentDevice', 'sd')
        ->innerJoin('sd.application', 'a')
        ->where('sd.enrollment_status = :enrollmentStatus')
        // ->andWhere('a.isEnrolledInBioTime = :enrolled')
        ->setParameter('enrollmentStatus', 'enrolled')
        // ->setParameter('enrolled', true)
        ->orderBy('a.full_name', 'ASC');

    // Apply filters
    if ($instituteId) {
        $qb->andWhere('a.institute = :instituteId')
            ->setParameter('instituteId', $instituteId);
    }

    if ($courseId) {
        $qb->andWhere('a.course = :courseId')
            ->setParameter('courseId', $courseId);
    }

    if ($employeeCode) {
        $qb->andWhere('a.biotimeEmployeeCode LIKE :employeeCode')
            ->setParameter('employeeCode', '%' . $employeeCode . '%');
    }

    if ($enrollmentDateFrom) {
        $dateFrom = new \DateTime($enrollmentDateFrom);
        $qb->andWhere('sd.enrollment_date >= :dateFrom')
            ->setParameter('dateFrom', $dateFrom);
    }

    if ($enrollmentDateTo) {
        $dateTo = new \DateTime($enrollmentDateTo . ' 23:59:59');
        $qb->andWhere('sd.enrollment_date <= :dateTo')
            ->setParameter('dateTo', $dateTo);
    }

    // Get the student devices - USE THE QUERY BUILDER!
    $studentDevices = $qb->getQuery()->getResult();

    // Debug: Check what we got
    // dd($studentDevices);

    // If no results, show empty state
    if (count($studentDevices) === 0) {
        return $this->render('admin/biotime/enrolled_students.html.twig', [
            'studentDevices' => [],
            'institutes' => $instituteRepository->findAll(),
            'courses' => $courseRepository->findAll(),
            'instituteGroups' => [],
            'courseGroups' => [],
            'enrollmentByMonth' => [],
            'filters' => [
                'institute' => $instituteId,
                'course' => $courseId,
                'employee_code' => $employeeCode,
                'enrollment_date_from' => $enrollmentDateFrom,
                'enrollment_date_to' => $enrollmentDateTo,
            ],
            'statistics' => [
                'total_enrolled' => 0,
                'total_institutes' => 0,
                'total_courses' => 0,
                'total_student_devices' => 0,
                'total_biotime_employees' => 0,
            ]
        ]);
    }

    // Group data for statistics
    $instituteGroups = [];
    $courseGroups = [];
    $enrollmentByMonth = [];
    
    foreach ($studentDevices as $studentDevice) {
        $application = $studentDevice->getApplication();
        if ($application) {
            // Institute grouping
            $institute = $application->getInstitute();
            $instituteName = $institute ? $institute->getName() : 'No Institute';
            if (!isset($instituteGroups[$instituteName])) {
                $instituteGroups[$instituteName] = 0;
            }
            $instituteGroups[$instituteName]++;
            
            // Course grouping
            $course = $application->getCourse();
            $courseName = $course ? $course->getName() : 'No Course';
            if (!isset($courseGroups[$courseName])) {
                $courseGroups[$courseName] = 0;
            }
            $courseGroups[$courseName]++;
            
            // Enrollment month grouping
            $enrollmentDate = $studentDevice->getEnrollmentDate();
            if ($enrollmentDate) {
                $month = $enrollmentDate->format('Y-m');
                if (!isset($enrollmentByMonth[$month])) {
                    $enrollmentByMonth[$month] = 0;
                }
                $enrollmentByMonth[$month]++;
            }
        }
    }

    // Sort by month
    ksort($enrollmentByMonth);

    // Get data for filters
    $institutes = $instituteRepository->findAll();
    $courses = $courseRepository->findAll();

    // Calculate statistics
    $totalEnrolled = count($studentDevices);
    
    // Extract unique institute and course IDs
    $instituteIds = [];
    $courseIds = [];
    
    foreach ($studentDevices as $studentDevice) {
        $application = $studentDevice->getApplication();
        if ($application) {
            if ($application->getInstitute()) {
                $instituteIds[] = $application->getInstitute()->getId();
            }
            if ($application->getCourse()) {
                $courseIds[] = $application->getCourse()->getId();
            }
        }
    }
    
    $totalInstitutes = count(array_unique($instituteIds));
    $totalCourses = count(array_unique($courseIds));

    // Calculate unique BioTime employees
    $uniqueBioTimeEmployees = [];
    foreach ($studentDevices as $studentDevice) {
        $employeeId = $studentDevice->getBiotimeEmployeeId();
        if ($employeeId) {
            $uniqueBioTimeEmployees[$employeeId] = true;
        }
    }

    $totalBioTimeEmployees = count($uniqueBioTimeEmployees);

    return $this->render('admin/biotime/enrolled_students.html.twig', [
        'studentDevices' => $studentDevices, // Pass student devices
        'institutes' => $institutes,
        'courses' => $courses,
        'instituteGroups' => $instituteGroups,
        'courseGroups' => $courseGroups,
        'enrollmentByMonth' => $enrollmentByMonth,
        'filters' => [
            'institute' => $instituteId,
            'course' => $courseId,
            'employee_code' => $employeeCode,
            'enrollment_date_from' => $enrollmentDateFrom,
            'enrollment_date_to' => $enrollmentDateTo,
        ],
        'statistics' => [
            'total_enrolled' => $totalEnrolled,
            'total_institutes' => $totalInstitutes,
            'total_courses' => $totalCourses,
            'total_student_devices' => $totalEnrolled,
            'total_biotime_employees' => $totalBioTimeEmployees,
        ]
    ]);
}

    #[Route('/ajax/enrolled-student-details/{id}', name: 'app_admin_biotime_ajax_enrolled_student_details')]
    public function ajaxEnrolledStudentDetails(
        int $id,
        EntityManagerInterface $em,
        KaabaAttendanceRepository $attendanceRepository
    ): JsonResponse {
        try {
            $application = $em->getRepository(KaabaApplication::class)->find($id);

            if (!$application || !$application->isEnrolledInBioTime()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Student not found or not enrolled'
                ], 404);
            }

            // Get student devices
            $devices = [];
            foreach ($application->getStudentDevices() as $studentDevice) {
                $device = $studentDevice->getDevice();
                if ($device) {
                    $devices[] = [
                        'device_name' => $device->getDeviceName(),
                        'device_type' => $device->getDeviceType(),
                        'status' => $device->getStatus(),
                        'area_name' => $device->getArea() ? $device->getArea()->getAreaName() : 'N/A'
                    ];
                }
            }

            // Get attendance summary (last 30 days)
            $startDate = new \DateTime('-30 days');
            $endDate = new \DateTime();
            $attendanceSummary = $attendanceRepository->createQueryBuilder('a')
                ->select([
                    'COUNT(a.id) as total_days',
                    'SUM(CASE WHEN a.status = \'present\' THEN 1 ELSE 0 END) as present_days',
                    'MAX(a.attendance_date) as last_attendance'
                ])
                ->where('a.application = :application')
                ->andWhere('a.attendance_date BETWEEN :startDate AND :endDate')
                ->setParameter('application', $application)
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->getQuery()
                ->getOneOrNullResult();

            return new JsonResponse([
                'success' => true,
                'student' => [
                    'uuid' => $application->getUuid(),
                    'full_name' => $application->getFullName(),
                    'employee_code' => $application->getBiotimeEmployeeCode(),
                    'employee_id' => $application->getBiotimeEmployeeId(),
                    'phone' => $application->getPhone(),
                    'email' => $application->getEmail(),
                    'institute' => $application->getInstitute()?->getName(),
                    'course' => $application->getCourse()?->getName(),
                    'enrollment_date' => $application->getBiotimeEnrollmentDate() ?
                        $application->getBiotimeEnrollmentDate()->format('Y-m-d H:i:s') : null
                ],
                'devices' => $devices,
                'attendance_summary' => $attendanceSummary
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/enrolled-students/export', name: 'app_admin_biotime_enrolled_students_export')]
    public function exportEnrolledStudents(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // Get filter parameters (same as above)
        $instituteId = $request->query->get('institute');
        $courseId = $request->query->get('course');

        // Build query (same as above)
        $qb = $em->createQueryBuilder();
        $qb->select('a', 'i', 'c')
            ->from('App\Entity\KaabaApplication', 'a')
            ->leftJoin('a.institute', 'i')
            ->leftJoin('a.course', 'c')
            ->where('a.isEnrolledInBioTime = :enrolled')
            ->setParameter('enrolled', true)
            ->orderBy('a.full_name', 'ASC');

        if ($instituteId) {
            $qb->andWhere('a.institute = :instituteId')
                ->setParameter('instituteId', $instituteId);
        }

        if ($courseId) {
            $qb->andWhere('a.course = :courseId')
                ->setParameter('courseId', $courseId);
        }

        $applications = $qb->getQuery()->getResult();

        // Generate CSV
        $csvData = [];
        $csvData[] = ['Student ID', 'Full Name', 'Employee Code', 'Employee ID', 'Institute', 'Course', 'Phone', 'Email', 'Enrollment Date', 'Status'];

        foreach ($applications as $application) {
            $csvData[] = [
                $application->getUuid(),
                $application->getFullName(),
                $application->getBiotimeEmployeeCode() ?? 'N/A',
                $application->getBiotimeEmployeeId() ?? 'N/A',
                $application->getInstitute()?->getName() ?? 'N/A',
                $application->getCourse()?->getName() ?? 'N/A',
                $application->getPhone() ?? 'N/A',
                $application->getEmail() ?? 'N/A',
                $application->getBiotimeEnrollmentDate() ? $application->getBiotimeEnrollmentDate()->format('Y-m-d H:i:s') : 'N/A',
                'Enrolled'
            ];
        }

        // Create CSV response
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="enrolled_students_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        fclose($output);

        return $response;
    }

    // ========== ATTENDANCE SYNC ==========

    #[Route('/attendance-sync', name: 'app_admin_biotime_attendance_sync')]
    public function attendanceSync(
        Request $request,
        KaabaBiotimeAreaRepository $areaRepository,
        BioTimeIntegrationService $integrationService
    ): Response {
        $areas = $areaRepository->findAll();

        // Default date range (last 7 days)
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->modify('-7 days');

        $syncResult = null;

        // Handle sync request
        if ($request->isMethod('POST')) {
            $areaId = $request->request->get('area_id');
            $startDateStr = $request->request->get('start_date');
            $endDateStr = $request->request->get('end_date');

            $area = $areaRepository->find($areaId);

            if ($area) {
                $startDate = new \DateTime($startDateStr);
                $endDate = new \DateTime($endDateStr);

                $syncResult = $integrationService->syncAttendanceForArea($area, $startDate, $endDate);

                if ($syncResult['success']) {
                    $this->addFlash('success', $syncResult['message']);
                } else {
                    $this->addFlash('error', $syncResult['message']);
                }
            } else {
                $this->addFlash('error', 'Area not found.');
            }
        }

        return $this->render('admin/biotime/attendance_sync.html.twig', [
            'areas' => $areas,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'syncResult' => $syncResult,
        ]);
    }

    // ========== API STATUS ==========

    #[Route('/api-status', name: 'app_admin_biotime_api_status')]
    public function apiStatus(
        BioTimeIntegrationService $integrationService
    ): Response {
        $status = $integrationService->getApiStatus();

        return $this->render('admin/biotime/api_status.html.twig', [
            'status' => $status,
        ]);
    }

    // ========== AJAX ENDPOINTS ==========

    #[Route('/ajax/test-connection', name: 'app_admin_biotime_ajax_test_connection')]
    public function ajaxTestConnection(
        BioTimeIntegrationService $integrationService
    ): JsonResponse {
        try {
            $result = $integrationService->testAuthentication();
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/ajax/sync-areas', name: 'app_admin_biotime_ajax_sync_areas')]
    public function ajaxSyncAreas(
        BioTimeIntegrationService $integrationService
    ): JsonResponse {
        try {
            $result = $integrationService->syncAreas();
            return new JsonResponse($result);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/ajax/get-area-details/{id}', name: 'app_admin_biotime_ajax_get_area_details')]
    public function ajaxGetAreaDetails(
        int $id,
        KaabaBiotimeAreaRepository $areaRepository
    ): JsonResponse {
        try {
            $area = $areaRepository->find($id);

            if (!$area) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Area not found'
                ], 404);
            }

            $devices = [];
            foreach ($area->getDevices() as $device) {
                $devices[] = [
                    'id' => $device->getId(),
                    'name' => $device->getDeviceName(),
                    'type' => $device->getDeviceType(),
                    'status' => $device->getStatus()
                ];
            }

            return new JsonResponse([
                'success' => true,
                'area' => [
                    'id' => $area->getId(),
                    'area_id' => $area->getAreaId(),
                    'name' => $area->getAreaName(),
                    'institute' => $area->getInstitute() ? [
                        'id' => $area->getInstitute()->getId(),
                        'name' => $area->getInstitute()->getName()
                    ] : null,
                    'devices' => $devices,
                    'devices_count' => count($devices)
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}