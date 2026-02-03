<?php

namespace App\Controller;

use App\Entity\KaabaAttendance;
use App\Service\BioTimeService;
use App\Entity\KaabaApplication;
use App\Entity\KaabaBiotimeArea;
use App\Entity\KaabaBiotimeDevice;
use App\Entity\KaabaStudentDevice;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\KaabaCourseRepository;
use App\Service\BioTimeIntegrationService;
use App\Repository\KaabaInstituteRepository;
use App\Repository\KaabaAttendanceRepository;
use App\Service\BioTimeAttendanceSyncService;
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

    private EntityManagerInterface $entityManager;
    private BioTimeService $bioTimeService;
    public function __construct(
        EntityManagerInterface $entityManager,
        BioTimeService $bioTimeService
    ) {
        $this->entityManager = $entityManager;
        $this->bioTimeService = $bioTimeService;
    }
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

    // Get selected area ID
    $selectedAreaId = $request->query->get('area');
    $selectedArea = null;
    
    if ($selectedAreaId) {
        $selectedArea = $areaRepository->find($selectedAreaId);
    }

    // Build query for applications
    $qb = $em->createQueryBuilder();
    $qb->select('a')
        ->from('App\Entity\KaabaApplication', 'a')
        ->leftJoin('App\Entity\KaabaStudentDevice', 'sd', 'WITH', 'sd.application = a')
        ->where('a.status = :statusId')
        ->andWhere('sd.biotime_employee_id IS NULL')
        ->setParameter('statusId', 4)
        ->orderBy('a.full_name', 'ASC');

    // Filter by institute if area is selected and has institute
    if ($selectedArea && $selectedArea->getInstitute()) {
        $institute = $selectedArea->getInstitute();
        $qb->andWhere('a.institute = :institute')
            ->setParameter('institute', $institute);
    }

    $applications = $qb->getQuery()->getResult();

    // Handle enrollment request
    $enrollApplicationId = $request->query->get('enroll');
    $enrollAreaId = $request->query->get('area');

    if ($enrollApplicationId && $enrollAreaId) {
        $application = $em->getRepository(KaabaApplication::class)->find($enrollApplicationId);
        $area = $areaRepository->find($enrollAreaId);

        if ($application && $area) {
            // Check if application belongs to area's institute
            if ($area->getInstitute() && $application->getInstitute()?->getId() !== $area->getInstitute()->getId()) {
                $this->addFlash('error', 'Student does not belong to the institute assigned to this area.');
                return $this->redirectToRoute('app_admin_biotime_enrollment', ['area' => $enrollAreaId]);
            }

            $result = $integrationService->enrollStudentInBioTime($application, $area);

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
            } else {
                $this->addFlash('error', $result['message']);
            }
        } else {
            $this->addFlash('error', 'Application or area not found.');
        }

        return $this->redirectToRoute('app_admin_biotime_enrollment', ['area' => $enrollAreaId]);
    }

    // Handle bulk enrollment
    if ($request->query->has('bulk_enroll') && $selectedAreaId) {
        $selectedArea = $areaRepository->find($selectedAreaId);
        
        if ($selectedArea && $selectedArea->getInstitute()) {
            $enrolledCount = 0;
            $errorCount = 0;
            $errorMessages = [];

            // Get student IDs from request (for manual selection) or use all filtered applications
            $studentIds = $request->query->all('student_ids') ?? [];
            
            if (empty($studentIds)) {
                // If no specific IDs, enroll all filtered applications for this area's institute
                $applicationsToEnroll = $applications;
            } else {
                // Filter applications by selected IDs and institute
                $qb = $em->createQueryBuilder();
                $qb->select('a')
                    ->from('App\Entity\KaabaApplication', 'a')
                    ->where('a.id IN (:ids)')
                    ->andWhere('a.status = :statusId')
                    ->andWhere('a.institute = :institute')
                    ->leftJoin('App\Entity\KaabaStudentDevice', 'sd', 'WITH', 'sd.application = a')
                    ->andWhere('sd.biotime_employee_id IS NULL')
                    ->setParameter('ids', $studentIds)
                    ->setParameter('statusId', 4)
                    ->setParameter('institute', $selectedArea->getInstitute());

                $applicationsToEnroll = $qb->getQuery()->getResult();
            }

            foreach ($applicationsToEnroll as $application) {
                $result = $integrationService->enrollStudentInBioTime($application, $selectedArea);

                if ($result['success']) {
                    $enrolledCount++;
                } else {
                    $errorCount++;
                    $errorMessages[] = "{$application->getFullName()}: {$result['message']}";
                }
            }

            if ($errorCount > 0) {
                $this->addFlash('warning', "Bulk enrollment completed with {$errorCount} error(s). Successfully enrolled: {$enrolledCount} students.");
                
                // Store error messages in session if there are too many
                if (count($errorMessages) > 0) {
                    $request->getSession()->set('bulk_enroll_errors', $errorMessages);
                }
            } else {
                $this->addFlash('success', "Successfully enrolled {$enrolledCount} students.");
            }
        } else {
            $this->addFlash('error', 'Area not found or no institute assigned.');
        }

        return $this->redirectToRoute('app_admin_biotime_enrollment', ['area' => $selectedAreaId]);
    }

    // Display bulk enrollment errors if any
    $bulkErrors = $request->getSession()->get('bulk_enroll_errors', []);
    if (!empty($bulkErrors)) {
        $request->getSession()->remove('bulk_enroll_errors');
    }

    return $this->render('admin/biotime/enrollment.html.twig', [
        'areas' => $areas,
        'applications' => $applications,
        'selectedArea' => $selectedArea,
        'bulkErrors' => $bulkErrors,
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

    // Get current user
    $user = $this->getUser();
    $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles());

    // Get institutes managed by current user (if not super admin)
    $managedInstitutes = [];
    if (!$isSuperAdmin) {
        $managedInstitutes = $user->getKaabaInstitutes()->toArray();
        $managedInstituteIds = array_map(fn($institute) => $institute->getId(), $managedInstitutes);
    }

    // Create query builder
    $qb = $em->createQueryBuilder();
    $qb->select('sd')
        ->from('App\Entity\KaabaStudentDevice', 'sd')
        ->innerJoin('sd.application', 'a')
        ->where('sd.enrollment_status = :enrollmentStatus')
        ->setParameter('enrollmentStatus', 'enrolled')
        ->orderBy('a.full_name', 'ASC');

    // Apply institute restriction for non-super admins
    if (!$isSuperAdmin) {
        if (empty($managedInstituteIds)) {
            // User manages no institutes, return empty result
            $studentDevices = [];
            
            return $this->render('admin/biotime/enrolled_students.html.twig', [
                'studentDevices' => [],
                'institutes' => $managedInstitutes, // Only show managed institutes
                'courses' => [],
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
                ],
                'is_super_admin' => $isSuperAdmin
            ]);
        }
        
        $qb->andWhere('a.institute IN (:managedInstitutes)')
            ->setParameter('managedInstitutes', $managedInstituteIds);
    }

    // Apply filters
    if ($instituteId) {
        // Additional check for non-super admins
        if (!$isSuperAdmin) {
            // Verify the selected institute is managed by the user
            $selectedInstitute = $instituteRepository->find($instituteId);
            if (!$selectedInstitute || !in_array($selectedInstitute->getId(), $managedInstituteIds)) {
                throw $this->createAccessDeniedException('You do not have permission to view this institute.');
            }
        }
        
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

    // Get the student devices
    $studentDevices = $qb->getQuery()->getResult();

    // If no results, show empty state
    if (count($studentDevices) === 0) {
        return $this->render('admin/biotime/enrolled_students.html.twig', [
            'studentDevices' => [],
            'institutes' => $isSuperAdmin ? $instituteRepository->findAll() : $managedInstitutes,
            'courses' => $courseRepository->findBy(['institute' => $managedInstitutes]),
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
            ],
            'is_super_admin' => $isSuperAdmin
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

    // Get data for filters - restrict for non-super admins
    $institutes = $isSuperAdmin ? $instituteRepository->findAll() : $managedInstitutes;
    $courses = $isSuperAdmin ? $courseRepository->findAll() : $courseRepository->findBy(['institute' => $managedInstitutes]);

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
        'studentDevices' => $studentDevices,
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
        ],
        'is_super_admin' => $isSuperAdmin
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
    EntityManagerInterface $em,
    KaabaStudentDeviceRepository $studentDeviceRepository
): Response {
    // Get filter parameters
    $instituteId = $request->query->get('institute');
    $courseId = $request->query->get('course');
    $employeeCode = $request->query->get('employee_code');
    $enrollmentDateFrom = $request->query->get('enrollment_date_from');
    $enrollmentDateTo = $request->query->get('enrollment_date_to');

    // Get current user
    $user = $this->getUser();
    $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $user->getRoles());

    // Get institutes managed by current user (if not super admin)
    $managedInstituteIds = [];
    if (!$isSuperAdmin) {
        $managedInstitutes = $user->getKaabaInstitutes()->toArray();
        $managedInstituteIds = array_map(fn($institute) => $institute->getId(), $managedInstitutes);
        
        if (empty($managedInstituteIds)) {
            throw $this->createAccessDeniedException('You do not have permission to export any data.');
        }
    }

    // Build query using KaabaStudentDevice
    $qb = $em->createQueryBuilder();
    $qb->select('sd', 'a', 'i', 'c')
        ->from('App\Entity\KaabaStudentDevice', 'sd')
        ->innerJoin('sd.application', 'a')
        ->leftJoin('a.institute', 'i')
        ->leftJoin('a.course', 'c')
        ->where('sd.enrollment_status = :enrollmentStatus')
        ->setParameter('enrollmentStatus', 'enrolled')
        ->orderBy('a.full_name', 'ASC');

    // Apply institute restriction for non-super admins
    if (!$isSuperAdmin) {
        $qb->andWhere('a.institute IN (:managedInstitutes)')
            ->setParameter('managedInstitutes', $managedInstituteIds);
    }

    // Apply filters
    if ($instituteId) {
        // Additional check for non-super admins
        if (!$isSuperAdmin && !in_array($instituteId, $managedInstituteIds)) {
            throw $this->createAccessDeniedException('You do not have permission to export data from this institute.');
        }
        
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

    $studentDevices = $qb->getQuery()->getResult();

    // Generate CSV
    $csvData = [];
    $csvData[] = ['Student ID', 'Full Name', 'Employee Code', 'BioTime Employee ID', 'Institute', 'Course', 'Phone', 'Email', 'Enrollment Date', 'Device', 'Area', 'Status'];

    foreach ($studentDevices as $studentDevice) {
        $application = $studentDevice->getApplication();
        $device = $studentDevice->getDevice();
        
        $csvData[] = [
            $application->getUuid(),
            $application->getFullName(),
            $application->getBiotimeEmployeeCode() ?? 'N/A',
            $studentDevice->getBiotimeEmployeeId() ?? 'N/A',
            $application->getInstitute()?->getName() ?? 'N/A',
            $application->getCourse()?->getName() ?? 'N/A',
            $application->getPhone() ?? 'N/A',
            $application->getEmail() ?? 'N/A',
            $studentDevice->getEnrollmentDate() ? $studentDevice->getEnrollmentDate()->format('Y-m-d H:i:s') : 'N/A',
            $device?->getDeviceName() ?? 'N/A',
            $device?->getArea()?->getAreaName() ?? 'N/A',
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


   #[Route('/transactions', name: 'app_admin_biotime_transactions', methods: ['GET'])]
public function transactions(Request $request): JsonResponse
{
    try {
        $params = $request->query->all();

        /**
         * If ?today=1 is passed, override start_time & end_time
         */
        if ($request->query->getBoolean('today')) {
            $today = new \DateTime('today');

            $params['start_time'] = $today->format('Y-m-d 00:00:00');
            $params['end_time']   = $today->format('Y-m-d 23:59:59');

            unset($params['today']); // clean API params
        }

        $transactions = $this->bioTimeService->getTransactions($params);

        return $this->json([
            'success' => true,
            'filters' => $params,
            'count'   => $transactions['count'] ?? 0,
            'data'    => $transactions['data'] ?? [],
            'next'    => $transactions['next'] ?? null,
            'previous'=> $transactions['previous'] ?? null,
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

#[Route('/attendance/sync-attendance', name: 'app_admin_biotime_sync_attendance', methods: ['POST', 'GET'])]
    public function syncAttendance(
        Request $request,
        BioTimeService $bioTimeService,
        BioTimeAttendanceSyncService $attendanceSyncService
    ): JsonResponse {
        try {
            // Optional filters
            $from = $request->query->get('from'); // Y-m-d
            $to   = $request->query->get('to');   // Y-m-d

            // 1. Fetch transactions from BioTime
            $transactions = $bioTimeService->getAttendanceTransactions(
                $from,
                $to
            );

            // 2. Sync to local DB
            $attendanceSyncService->sync($transactions);

            return $this->json([
                'status' => 'success',
                'message' => 'BioTime attendance synchronized successfully',
                'transactions_received' => count($transactions),
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Attendance sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}