<?php
// src/Controller/Admin/AttendanceController.php

namespace App\Controller;

use App\Entity\KaabaApplication;
use App\Entity\KaabaAttendance;
use App\Entity\KaabaConfigSchoolDay;
use App\Entity\KaabaConfigSchoolHoliday;
use App\Entity\KaabaConfigSchoolHour;
use App\Entity\KaabaInstitute;
use App\Entity\KaabaStudentExcuse;
use App\Form\AttendanceSearchType;
use App\Form\ExcuseConfirmationType;
use App\Form\StudentExcuseType;
use App\Repository\KaabaApplicationRepository;
use App\Repository\KaabaAttendanceRepository;
use App\Repository\KaabaConfigSchoolDayRepository;
use App\Repository\KaabaConfigSchoolHolidayRepository;
use App\Repository\KaabaConfigSchoolHourRepository;
use App\Repository\KaabaCourseRepository;
use App\Repository\KaabaInstituteRepository;
use App\Repository\KaabaStudentExcuseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;

#[Route('/admin/attendance')]
class AttendanceController extends AbstractController
{
    private $entityManager;
    private $attendanceRepository;
    private $applicationRepository;
    private $instituteRepository;
    private $schoolDayRepository;
    private $holidayRepository;
    private $schoolHourRepository;

    private $excuseRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        KaabaAttendanceRepository $attendanceRepository,
        KaabaApplicationRepository $applicationRepository,
        KaabaInstituteRepository $instituteRepository,
        KaabaConfigSchoolDayRepository $schoolDayRepository,
        KaabaConfigSchoolHolidayRepository $holidayRepository,
        KaabaConfigSchoolHourRepository $schoolHourRepository,
        KaabaCourseRepository $courseRepository,
        KaabaStudentExcuseRepository $excuseRepository
    ) {
        $this->entityManager = $entityManager;
        $this->attendanceRepository = $attendanceRepository;
        $this->applicationRepository = $applicationRepository;
        $this->instituteRepository = $instituteRepository;
        $this->schoolDayRepository = $schoolDayRepository;
        $this->holidayRepository = $holidayRepository;
        $this->schoolHourRepository = $schoolHourRepository;
        $this->courseRepository = $courseRepository;
        $this->excuseRepository = $excuseRepository;
        
    }


#[Route('/', name: 'app_admin_attendance_index', methods: ['GET'])]
public function index(Request $request): Response
{
    $user = $this->getUser();
    
    // Get institutes managed by current user
    if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
        $managedInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
        
        // If user doesn't manage any institutes, show message
        if (empty($managedInstitutes)) {
            $this->addFlash('warning', 'You do not manage any institutes to view attendance.');
            return $this->render('admin/attendance/index.html.twig', [
                'no_institutes' => true,
                'attendanceData' => [],
                'stats' => [],
                'selectedDate' => new \DateTime()
            ]);
        }
    } else {
        // Super admin or users with special roles can see all institutes
        $managedInstitutes = $this->instituteRepository->findAll();
    }

    // Create search form with managed institutes
    $searchForm = $this->createForm(AttendanceSearchType::class, null, [
        'method' => 'GET',
        'csrf_protection' => false,
        'institutes' => $managedInstitutes,
        'request' => $request
    ]);

    $searchForm->handleRequest($request);
    
    // Get filter values
    $date = $searchForm->get('date')->getData() ?? new \DateTime();
    $applicant = $searchForm->get('applicant')->getData();
    $institute = $searchForm->get('institute')->getData();
    $course = $searchForm->get('course')->getData();
    $status = $searchForm->get('status')->getData();

    // Get course ID from request if available
    $courseId = $request->query->get('course');
    if ($courseId && !$course) {
        // If course is selected in URL but not in form data, try to find it
        $course = $this->courseRepository->find($courseId); // FIXED: Removed extra $ sign
    }
    
    // Convert date to string for template
    $dateString = $date->format('Y-m-d');
    
    // Check if it's a holiday
    $isHoliday = $this->isHoliday($date);
    
    // Check if it's a school day
    $dayOfWeek = $date->format('l');
    $isSchoolDay = $this->isSchoolDay($dayOfWeek);
    
    // Get attendance data - UPDATED to include all students
    $attendanceData = $this->getDailyAttendanceWithAbsent($date, $applicant, $institute, $course, $status);
    
    // Calculate statistics (only for school days)
    if ($isSchoolDay && !$isHoliday) {
        $stats = $this->calculateAttendanceStatistics($attendanceData);
    } else {
        $stats = [
            'total_students' => 0,
            'present' => 0,
            'absent' => 0,
            'attendance_rate' => 0,
            'total_hours' => 0,
            'is_holiday' => $isHoliday,
            'is_school_day' => $isSchoolDay
        ];
    }

    return $this->render('admin/attendance/index.html.twig', [
        'attendanceData' => $attendanceData,
        'selectedDate' => $date,
        'stats' => $stats,
        'isHoliday' => $isHoliday,
        'isSchoolDay' => $isSchoolDay,
        'searchForm' => $searchForm->createView(),
        'no_institutes' => false,
        'selected_course_id' => $courseId,
    ]);
}


#[Route('/courses-by-institute/{instituteId}', name: 'app_admin_attendance_courses_by_institute', methods: ['GET'])]
public function getCoursesByInstitute(int $instituteId, Request $request, KaabaCourseRepository $courseRepository): JsonResponse
{
    // Validate institute ID
    if ($instituteId <= 0) {
        return $this->json([
            'courses' => [],
            'error' => 'Invalid institute ID'
        ]);
    }
    
    // Get courses for the institute
    $courses = $courseRepository->findBy(['institute' => $instituteId]);
    
    $courseArray = [];
    foreach ($courses as $course) {
        $courseArray[] = [
            'id' => $course->getId(),
            'name' => $course->getName()
        ];
    }
    
    return $this->json([
        'courses' => $courseArray,
        'instituteId' => $instituteId,
        'count' => count($courseArray)
    ]);
}

private function getDailyAttendanceWithAbsent(
    \DateTime $date, 
    $applicant = null, 
    $institute = null, 
    $course = null, 
    $status = null
): array {
    $startDate = clone $date;
    $startDate->setTime(0, 0, 0);
    $endDate = clone $date;
    $endDate->setTime(23, 59, 59);

    // Get ALL students who are enrolled (have student device)
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('app', 'sd', 'inst', 'course')
        ->from(KaabaApplication::class, 'app')
        ->leftJoin('app.studentDevice', 'sd')
        ->leftJoin('app.institute', 'inst')
        ->leftJoin('app.course', 'course')
        ->where('sd IS NOT NULL') // Only students with student device (enrolled)
        ->orderBy('sd.created_at', 'DESC');
    
    // Filter by applicant
    if ($applicant) {
        $qb->andWhere('app = :applicant')
            ->setParameter('applicant', $applicant);
    }
    
    // Filter by institute
    if ($institute) {
        $qb->andWhere('app.institute = :institute')
            ->setParameter('institute', $institute);
    } else {
        $user = $this->getUser();
        if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $managedInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
            if (!empty($managedInstitutes)) {
                $qb->andWhere('app.institute IN (:institutes)')
                    ->setParameter('institutes', $managedInstitutes);
            }
        }
    }
    
    // Filter by course
    if ($course) {
        $qb->andWhere('app.course = :course')
            ->setParameter('course', $course);
    }
    
    $allStudents = $qb->getQuery()->getResult();
    
    // Get attendance records for the date
    $attendanceQb = $this->entityManager->createQueryBuilder();
    $attendanceQb->select('att')
        ->from(KaabaAttendance::class, 'att')
        ->join('att.application', 'app2')
        ->where('att.attendance_date = :date')
        ->setParameter('date', $startDate)
        ->orderBy('att.check_in_time', 'ASC');
    
    if ($institute) {
        $attendanceQb->andWhere('att.institute = :institute')
            ->setParameter('institute', $institute);
    }
    
    $attendanceRecords = $attendanceQb->getQuery()->getResult();
    
    // Group attendance records by application
    $attendanceByApplication = [];
    foreach ($attendanceRecords as $record) {
        $appId = $record->getApplication()->getId();
        if (!isset($attendanceByApplication[$appId])) {
            $attendanceByApplication[$appId] = [];
        }
        $attendanceByApplication[$appId][] = $record;
    }
    
    // Get min hours per day from institute config
    $minHoursByInstitute = [];
    foreach ($allStudents as $student) {
        $instId = $student->getInstitute()?->getId();
        if ($instId && !isset($minHoursByInstitute[$instId])) {
            $institute = $this->instituteRepository->find($instId);
            if ($institute && $institute->getSchoolHoursConfig()) {
                $minHoursByInstitute[$instId] = $institute->getSchoolHoursConfig()->getMinHoursPerDay() ?? 0;
            } else {
                $minHoursByInstitute[$instId] = 0;
            }
        }
    }
    
    // Process each student
    $result = [];
    foreach ($allStudents as $student) {
        $appId = $student->getId();
        $records = $attendanceByApplication[$appId] ?? [];
        
        // Check if student is excused
        $excuse = $this->isStudentExcused($student, $date);
        $isExcused = $excuse !== null;
        $excuseReason = $isExcused ? $excuse->getReason() : null;
        $excuseHoursPerDay = $isExcused ? $excuse->getExcusedHoursPerDay() : null;
        
        if (empty($records)) {
            // Student has no attendance records
            if ($isExcused) {
                $studentStatus = 'excused';
            } else {
                $studentStatus = 'absent';
            }
            $checkInTime = null;
            $checkOutTime = null;
            $totalHours = 0;
            $lastRecord = null;
            $isVerified = false;
            $hasVirtualCheckout = false;
        } else {
            // Student has attendance records
            usort($records, function($a, $b) {
                return $a->getCheckInTime() <=> $b->getCheckInTime();
            });

            $firstRecord = $records[0];
            $lastRecord = end($records);
            
            $checkInTime = $firstRecord->getCheckInTime();
            $checkOutTime = $lastRecord->getCheckOutTime();
            
            if (!$checkOutTime) {
                $checkOutTime = $lastRecord->getCheckInTime();
                $hasVirtualCheckout = true;
            } else {
                $hasVirtualCheckout = false;
            }
            
            // Calculate total hours
            $totalHours = 0;
            if ($checkInTime && $checkOutTime) {
                $interval = $checkInTime->diff($checkOutTime);
                $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
            }

            $studentStatus = 'present';
            $isVerified = $lastRecord->isIsVerified();
        }
        
        // Apply status filter
        if ($status && $studentStatus !== $status) {
            continue;
        }
        
        // Get expected hours for the day
        $instId = $student->getInstitute()?->getId();
        $expectedHours = $minHoursByInstitute[$instId] ?? 0;
        
        // Calculate hours percentage
        $hoursPercentage = $expectedHours > 0 ? round(($totalHours / $expectedHours) * 100, 2) : 0;
        
        $result[] = [
            'application' => $student,
            'check_in' => $checkInTime,
            'check_out' => $checkOutTime,
            'total_hours' => $totalHours,
            'expected_hours' => $expectedHours,
            'hours_percentage' => $hoursPercentage,
            'status' => $studentStatus,
            'records_count' => count($records),
            'is_verified' => $isVerified,
            'attendance_id' => $lastRecord ? $lastRecord->getId() : null,
            'all_records' => $records,
            'has_virtual_checkout' => $hasVirtualCheckout ?? false,
            'institute' => $student->getInstitute(),
            'course' => $student->getCourse(),
            'created_at' => $student->getCreatedAt(),
            // Excuse data
            'is_excused' => $isExcused,
            'excuse_reason' => $excuseReason,
            'excuse_hours_per_day' => $excuseHoursPerDay,
            'excuse' => $excuse,
        ];
    }
    
    return $result;
}



   private function isSchoolDay(string $dayOfWeek): bool
    {
        $schoolDay = $this->schoolDayRepository->findOneBy([
            'dayOfWeek' => $dayOfWeek
        ]);
        
        return $schoolDay ? $schoolDay->isIsSchoolDay() : true; // Default to true if not configured
    }

    private function getDailyAttendance(\DateTime $date, $applicant, $institute, $status): array
    {
        $startDate = clone $date;
        $startDate->setTime(0, 0, 0);
        
        $endDate = clone $date;
        $endDate->setTime(23, 59, 59);

        // Build query based on filters
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('att')
            ->from(KaabaAttendance::class, 'att')
            ->join('att.application', 'app')
            ->where('att.attendance_date = :date')
            ->setParameter('date', $startDate)
            ->orderBy('att.check_in_time', 'ASC');

        // Filter by applicant
        if ($applicant) {
            $qb->andWhere('app = :applicant')
                ->setParameter('applicant', $applicant);
        }

        // Filter by institute
        if ($institute) {
            $qb->andWhere('att.institute = :institute')
                ->setParameter('institute', $institute);
        } else {
            // If no institute selected, filter by user's managed institutes
            $user = $this->getUser();
            if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
                $managedInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
                if (!empty($managedInstitutes)) {
                    $qb->andWhere('att.institute IN (:institutes)')
                        ->setParameter('institutes', $managedInstitutes);
                }
            }
        }

        $attendanceRecords = $qb->getQuery()->getResult();

        // Group by application
        $groupedByApplication = [];
        foreach ($attendanceRecords as $record) {
            $application = $record->getApplication();
            $applicationId = $application->getId();

            if (!isset($groupedByApplication[$applicationId])) {
                $groupedByApplication[$applicationId] = [];
            }

            $groupedByApplication[$applicationId][] = $record;
        }

        // Process each application's records
        $result = [];
        foreach ($groupedByApplication as $appId => $records) {
            if (count($records) > 0) {
                // Sort by check-in time
                usort($records, function($a, $b) {
                    return $a->getCheckInTime() <=> $b->getCheckInTime();
                });

                $firstRecord = $records[0];
                $lastRecord = end($records);
                
                // Get check-in and check-out times
                $checkInTime = $firstRecord->getCheckInTime();
                $checkOutTime = $lastRecord->getCheckOutTime();
                
                // If check-out is null, use the last check-in time as check-out
                if (!$checkOutTime) {
                    $checkOutTime = $lastRecord->getCheckInTime();
                }
                
                // Calculate total hours
                $totalHours = 0;
                if ($checkInTime && $checkOutTime) {
                    $interval = $checkInTime->diff($checkOutTime);
                    $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
                }

                // Get status from last record
                $recordStatus = $lastRecord->getStatus();
                
                // Apply status filter
                if ($status && $recordStatus !== $status) {
                    continue;
                }

                $result[$appId] = [
                    'application' => $firstRecord->getApplication(),
                    'check_in' => $checkInTime,
                    'check_out' => $checkOutTime,
                    'total_hours' => $totalHours,
                    'status' => $recordStatus,
                    'records_count' => count($records),
                    'is_verified' => $lastRecord->isIsVerified(),
                    'attendance_id' => $lastRecord->getId(),
                    'all_records' => $records,
                    'has_virtual_checkout' => !$lastRecord->getCheckOutTime(),
                    'institute' => $firstRecord->getInstitute()
                ];
            }
        }

        // Sort by application name
        usort($result, function($a, $b) {
            return strcmp($a['application']->getFullName(), $b['application']->getFullName());
        });

        return $result;
    }

    private function isHoliday(\DateTime $date): bool
    {
        $dateStr = $date->format('Y-m-d');
        
        // Check for fixed date holidays
        $holiday = $this->holidayRepository->createQueryBuilder('h')
            ->where(':date BETWEEN h.date AND COALESCE(h.endDate, h.date)')
            ->setParameter('date', $dateStr)
            ->getQuery()
            ->getOneOrNullResult();
            
        return $holiday !== null;
    }

//     private function getDailyAttendance(\DateTime $date, ?int $applicationId, ?string $status): array
// {
//     $startDate = clone $date;
//     $startDate->setTime(0, 0, 0);
    
//     $endDate = clone $date;
//     $endDate->setTime(23, 59, 59);

//     // Get all attendance records for the date
//     $criteria = [
//         'attendance_date' => $startDate,
//     ];

//     if ($applicationId) {
//         $criteria['application'] = $applicationId;
//     }

//     // Get ALL records for the date, not just verified ones
//     $attendanceRecords = $this->attendanceRepository->findBy($criteria, ['check_in_time' => 'ASC']);

//     // Group by application
//     $groupedByApplication = [];
//     foreach ($attendanceRecords as $record) {
//         $application = $record->getApplication();
//         $applicationId = $application->getId();

//         if (!isset($groupedByApplication[$applicationId])) {
//             $groupedByApplication[$applicationId] = [];
//         }

//         $groupedByApplication[$applicationId][] = $record;
//     }

//     // Process each application's records
//     $result = [];
//     foreach ($groupedByApplication as $appId => $records) {
//         if (count($records) > 0) {
//             // Sort by check-in time (already sorted, but ensure)
//             usort($records, function($a, $b) {
//                 return $a->getCheckInTime() <=> $b->getCheckInTime();
//             });

//             $firstRecord = $records[0];
//             $lastRecord = end($records);
            
//             // IMPORTANT: Use last check-in time as virtual check-out if no check-out exists
//             $checkInTime = $firstRecord->getCheckInTime();
//             $checkOutTime = $lastRecord->getCheckOutTime();
            
//             // If check-out is null, use the last check-in time as check-out
//             if (!$checkOutTime) {
//                 $checkOutTime = $lastRecord->getCheckInTime();
//             }
            
//             // Calculate total hours
//             $totalHours = 0;
//             if ($checkInTime && $checkOutTime) {
//                 $interval = $checkInTime->diff($checkOutTime);
//                 $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
                
//                 // Update the last record's check-out time and total hours if they're null
//                 if (!$lastRecord->getCheckOutTime()) {
//                     $lastRecord->setCheckOutTime($checkOutTime);
//                 }
//                 if (!$lastRecord->getTotalHours()) {
//                     $lastRecord->setTotalHours($totalHours);
//                     $this->entityManager->persist($lastRecord);
//                 }
//             }

//             // Apply status filter
//             if ($status && $lastRecord->getStatus() !== $status) {
//                 continue;
//             }

//             $result[$appId] = [
//                 'application' => $firstRecord->getApplication(),
//                 'check_in' => $checkInTime,
//                 'check_out' => $checkOutTime,
//                 'total_hours' => $totalHours,
//                 'status' => $lastRecord->getStatus(),
//                 'records_count' => count($records),
//                 'is_verified' => $lastRecord->isIsVerified(),
//                 'attendance_id' => $lastRecord->getId(),
//                 'all_records' => $records,
//                 'has_virtual_checkout' => !$lastRecord->getCheckOutTime() // Flag to indicate we used last check-in as check-out
//             ];
//         }
//     }

//     $this->entityManager->flush();

//     // Sort by application name
//     usort($result, function($a, $b) {
//         return strcmp($a['application']->getFullName(), $b['application']->getFullName());
//     });

//     return $result;
// }

    



#[Route('/summary', name: 'app_admin_attendance_summary', methods: ['GET'])]
    public function summary(Request $request): Response
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $applicationId = $request->query->get('application_id');

        // Default to current week if not specified
        if (!$startDate) {
            $startDate = (new \DateTime('monday this week'))->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = (new \DateTime('sunday this week'))->format('Y-m-d');
        }

        $startDateObj = \DateTime::createFromFormat('Y-m-d', $startDate);
        $endDateObj = \DateTime::createFromFormat('Y-m-d', $endDate);

        if (!$startDateObj) {
            $startDateObj = new \DateTime('monday this week');
        }
        if (!$endDateObj) {
            $endDateObj = new \DateTime('sunday this week');
        }

        // Adjust end date to include the entire day
        $endDateObj->setTime(23, 59, 59);

        $summaryData = $this->getAttendanceSummary($startDateObj, $endDateObj, $applicationId);

        return $this->render('admin/attendance/summary.html.twig', [
            'summaryData' => $summaryData,
            'startDate' => $startDateObj->format('Y-m-d'),
            'endDate' => $endDateObj->format('Y-m-d'),
            'application_id' => $applicationId,
        ]);
    }

    #[Route('/application/{id}', name: 'app_admin_attendance_application', methods: ['GET'])]
    public function applicationAttendance(int $id, Request $request): Response
    {
        $application = $this->applicationRepository->find($id);
        
        if (!$application) {
            $this->addFlash('error', 'Application not found.');
            return $this->redirectToRoute('app_admin_attendance_index');
        }

        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Default to last 30 days if not specified
        if (!$startDate) {
            $startDate = (new \DateTime('-30 days'))->format('Y-m-d');
        }
        if (!$endDate) {
            $endDate = (new \DateTime())->format('Y-m-d');
        }

        $startDateObj = \DateTime::createFromFormat('Y-m-d', $startDate);
        $endDateObj = \DateTime::createFromFormat('Y-m-d', $endDate);
        
        if (!$startDateObj) {
            $startDateObj = new \DateTime('-30 days');
        }
        if (!$endDateObj) {
            $endDateObj = new \DateTime();
        }

        // Adjust end date to include the entire day
        $endDateObj->setTime(23, 59, 59);

        // Get attendance for this specific application
        $attendanceRecords = $this->attendanceRepository->findByApplicationAndDateRange(
            $application,
            $startDateObj,
            $endDateObj
        );

        // Group by date
        $groupedAttendance = [];
        foreach ($attendanceRecords as $record) {
            $dateKey = $record->getAttendanceDate()->format('Y-m-d');
            if (!isset($groupedAttendance[$dateKey])) {
                $groupedAttendance[$dateKey] = [];
            }
            $groupedAttendance[$dateKey][] = $record;
        }

        // Process each day's records
        $processedAttendance = [];
        foreach ($groupedAttendance as $date => $records) {
            if (count($records) > 0) {
                // Sort by check-in time
                usort($records, function($a, $b) {
                    return $a->getCheckInTime() <=> $b->getCheckInTime();
                });

                $firstRecord = $records[0];
                $lastRecord = end($records);
                
                $processedAttendance[$date] = [
                    'check_in' => $firstRecord->getCheckInTime(),
                    'check_out' => $lastRecord->getCheckOutTime(),
                    'total_hours' => $lastRecord->getTotalHours(),
                    'status' => $lastRecord->getStatus(),
                    'records_count' => count($records),
                    'all_records' => $records
                ];
            }
        }

        // Calculate statistics
        $stats = [
            'total_days' => count($processedAttendance),
            'total_hours' => array_sum(array_column($processedAttendance, 'total_hours')),
            'average_hours_per_day' => count($processedAttendance) > 0 ? 
                array_sum(array_column($processedAttendance, 'total_hours')) / count($processedAttendance) : 0,
            'present_days' => count(array_filter($processedAttendance, function($item) {
                return in_array($item['status'], ['present', 'late', 'half-day']);
            })),
            'absent_days' => count(array_filter($processedAttendance, function($item) {
                return $item['status'] === 'absent';
            })),
        ];

        return $this->render('admin/attendance/application.html.twig', [
            'application' => $application,
            'attendanceData' => $processedAttendance,
            'stats' => $stats,
            'startDate' => $startDateObj->format('Y-m-d'),
            'endDate' => $endDateObj->format('Y-m-d'),
        ]);
    }

    #[Route('/{id}/verify', name: 'app_admin_attendance_verify', methods: ['POST'])]
    public function verifyAttendance(int $id, Request $request): Response
    {
        $attendance = $this->attendanceRepository->find($id);
        
        if (!$attendance) {
            $this->addFlash('error', 'Attendance record not found.');
            return $this->redirectToRoute('app_admin_attendance_index');
        }

        $isVerified = $request->request->get('is_verified') === '1';
        $verificationNotes = $request->request->get('verification_notes');

        $attendance->setIsVerified($isVerified);
        $attendance->setVerificationNotes($verificationNotes);
        $attendance->setVerifiedBy($this->getUser());
        $attendance->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->addFlash('success', 'Attendance record verified successfully.');

        return $this->redirectToRoute('app_admin_attendance_index', [
            'date' => $attendance->getAttendanceDate()->format('Y-m-d')
        ]);
    }

   // In src/Controller/Admin/AttendanceController.php


private function calculateAttendanceStatistics(array $attendanceData): array
{
    $stats = [
        'total_students' => count($attendanceData),
        'present' => 0,
        'absent' => 0,
        'verified' => 0,
        'total_hours' => 0,
        'attendance_rate' => 0,
    ];

    foreach ($attendanceData as $data) {
        $stats['total_hours'] += $data['total_hours'] ?? 0;
        
        if ($data['is_verified'] ?? false) {
            $stats['verified']++;
        }

        // Get the status from the data array
        $status = strtolower($data['status'] ?? 'absent');
        
        if ($status === 'present') {
            $stats['present']++;
        } else {
            $stats['absent']++;
        }
    }

    // Calculate attendance rate: present / total_students
    $stats['attendance_rate'] = $stats['total_students'] > 0 ? 
        round(($stats['present'] / $stats['total_students']) * 100, 2) : 0;

    return $stats;
}






    private function getAttendanceSummary(\DateTime $startDate, \DateTime $endDate, ?int $applicationId): array
    {
        // Get all applications with attendance in the date range
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
           ->from(KaabaAttendance::class, 'a')
           ->join('a.application', 'app')
           ->where('a.attendance_date BETWEEN :startDate AND :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate)
           ->orderBy('app.full_name', 'ASC')
           ->addOrderBy('a.attendance_date', 'ASC');

        if ($applicationId) {
            $qb->andWhere('app.id = :applicationId')
               ->setParameter('applicationId', $applicationId);
        }

        $attendanceRecords = $qb->getQuery()->getResult();

        // Group by application and date
        $groupedData = [];
        foreach ($attendanceRecords as $record) {
            $application = $record->getApplication();
            $appId = $application->getId();
            $dateKey = $record->getAttendanceDate()->format('Y-m-d');

            if (!isset($groupedData[$appId])) {
                $groupedData[$appId] = [
                    'application' => $application,
                    'dates' => [],
                    'total_hours' => 0,
                    'present_days' => 0,
                    'absent_days' => 0
                ];
            }

            if (!isset($groupedData[$appId]['dates'][$dateKey])) {
                $groupedData[$appId]['dates'][$dateKey] = [];
            }

            $groupedData[$appId]['dates'][$dateKey][] = $record;
        }

        // Process each application's data
        $summaryData = [];
        foreach ($groupedData as $appId => $appData) {
            $application = $appData['application'];
            $dates = $appData['dates'];

            $dailySummary = [];
            $totalHours = 0;
            $presentDays = 0;
            $absentDays = 0;

            foreach ($dates as $date => $records) {
                if (count($records) > 0) {
                    // Sort by check-in time
                    usort($records, function($a, $b) {
                        return $a->getCheckInTime() <=> $b->getCheckInTime();
                    });

                    $firstRecord = $records[0];
                    $lastRecord = end($records);

                    $totalHoursForDay = $lastRecord->getTotalHours() ?? 0;
                    $totalHours += $totalHoursForDay;

                    if (in_array($lastRecord->getStatus(), ['present', 'late', 'half-day'])) {
                        $presentDays++;
                    } elseif ($lastRecord->getStatus() === 'absent') {
                        $absentDays++;
                    }

                    $dailySummary[$date] = [
                        'check_in' => $firstRecord->getCheckInTime(),
                        'check_out' => $lastRecord->getCheckOutTime(),
                        'total_hours' => $totalHoursForDay,
                        'status' => $lastRecord->getStatus(),
                        'is_verified' => $lastRecord->isIsVerified()
                    ];
                }
            }

            $summaryData[] = [
                'application' => $application,
                'daily_summary' => $dailySummary,
                'total_hours' => $totalHours,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'average_hours_per_day' => count($dailySummary) > 0 ? $totalHours / count($dailySummary) : 0
            ];
        }

        return $summaryData;
    }






#[Route('/monthly-report', name: 'app_admin_attendance_monthly_report', methods: ['GET'])]
public function monthlyReport(Request $request): Response
{
    $user = $this->getUser();
    
    // Get institutes managed by current user
    if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
        $managedInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
        
        if (empty($managedInstitutes)) {
            $this->addFlash('warning', 'You do not manage any institutes to view attendance reports.');
            return $this->render('admin/attendance/monthly_report.html.twig', [
                'no_institutes' => true,
                'monthlyData' => [],
                'selectedYear' => date('Y'),
                'selectedMonth' => date('n'),
                'stats' => [],
                'total_items' => 0,
                'current_page' => 1,
                'total_pages' => 0,
                'per_page' => 20,
                'offset' => 0,
                'next_page' => 1,
                'prev_page' => 1,
                'has_next_page' => false,
                'has_prev_page' => false,
                'months' => [],
                'applicant_filter' => null,
                'institute_filter' => null,
                'course_filter' => null, // Add this
                'managedInstitutes' => [],
                'searchForm' => null,
                'courses' => []
            ]);
        }
    } else {
        // Super admin or users with special roles can see all institutes
        $managedInstitutes = $this->instituteRepository->findAll();
    }

    // ALWAYS default to current month/year on initial load
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    // Get filter parameters
    $year = $request->query->getInt('year', $currentYear);
    $month = $request->query->getInt('month', $currentMonth);
    $monthYear = $request->query->get('monthYear');
    
    // Pagination parameters
    $page = $request->query->getInt('page', 1);
    $perPage = $request->query->getInt('per_page', 20); // Default 20 per page
    
    // If monthYear is provided, use it (for the month picker)
    if ($monthYear) {
        $dateParts = explode('-', $monthYear);
        if (count($dateParts) === 2) {
            $year = (int)$dateParts[0];
            $month = (int)$dateParts[1];
        }
    }
    
    // Ensure month is always valid
    if ($month < 1 || $month > 12) {
        $month = $currentMonth;
        $year = $currentYear;
    }
    
    $applicantId = $request->query->get('applicant');
    $instituteId = $request->query->get('institute');
    $courseId = $request->query->get('course');
    
    // Get courses for the selected institute (if any)
    $courses = [];
    if ($instituteId) {
        $courses = $this->courseRepository->findBy(['institute' => $instituteId]);
    }
    
    $startDate = new \DateTime("{$year}-{$month}-01");
    $endDate = clone $startDate;
    $endDate->modify('last day of this month')->setTime(23, 59, 59);
    
    // Get ALL monthly data (for statistics) - Pass managed institutes AND course filter
    $allMonthlyData = $this->getMonthlyAttendanceDataWithRequiredHours(
        $startDate, 
        $endDate, 
        $applicantId, 
        $instituteId,
        $managedInstitutes,
        $courseId // Pass course filter
    );
    
    // Calculate statistics - UPDATED to include holiday count
    $stats = $this->calculateMonthlyStatistics($allMonthlyData, $startDate, $endDate);
    
    // Paginate the data
    $totalItems = count($allMonthlyData);
    $totalPages = ceil($totalItems / $perPage);
    
    // Ensure page is within bounds
    if ($page > $totalPages) {
        $page = max(1, $totalPages);
    }
    
    // Get paginated slice of data
    $offset = ($page - 1) * $perPage;
    $monthlyData = array_slice($allMonthlyData, $offset, $perPage);
    
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    // Create search form for applicant autocomplete
    $searchForm = $this->createForm(AttendanceSearchType::class, null, [
        'method' => 'GET',
        'csrf_protection' => false,
        'institutes' => $managedInstitutes // Pass only managed institutes to form
    ]);
    
    // Handle the form submission for applicant filter
    $searchForm->handleRequest($request);
    
    // If form was submitted with applicant, get the applicant ID
    if ($searchForm->isSubmitted() && $searchForm->isValid()) {
        $applicant = $searchForm->get('applicant')->getData();
        if ($applicant) {
            $applicantId = $applicant->getId();
        }
    }

    return $this->render('admin/attendance/monthly_report.html.twig', [
        'monthlyData' => $monthlyData,
        'selectedYear' => $year,
        'selectedMonth' => $month,
        'stats' => $stats,
        'months' => $months,
        'no_institutes' => false,
        'applicant_filter' => $applicantId,
        'institute_filter' => $instituteId,
        'course_filter' => $courseId,
        'managedInstitutes' => $managedInstitutes,
        'searchForm' => $searchForm->createView(),
        'courses' => $courses,
        // Pagination variables
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'offset' => $offset,
        'next_page' => min($page + 1, $totalPages),
        'prev_page' => max($page - 1, 1),
        'has_next_page' => $page < $totalPages,
        'has_prev_page' => $page > 1,
    ]);
}

private function getDaysPresentUpToToday(int $applicationId, \DateTime $startDate, \DateTime $today): int
{
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('COUNT(DISTINCT att.attendance_date)')
        ->from(KaabaAttendance::class, 'att')
        ->where('att.application = :applicationId')
        ->andWhere('att.attendance_date BETWEEN :startDate AND :today')
        ->setParameter('applicationId', $applicationId)
        ->setParameter('startDate', $startDate)
        ->setParameter('today', $today);
    
    return (int)$qb->getQuery()->getSingleScalarResult();
}

private function getMonthlyAttendanceDataWithRequiredHours(
    \DateTime $startDate, 
    \DateTime $endDate, 
    $applicantId = null, 
    $instituteId = null,
    array $managedInstitutes = [],
    $courseId = null
): array {
    // Build query to get all attendance records for the month
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select([
            'app.id as application_id',
            'app.full_name as full_name',
            'app.phone as phone',
            'inst.id as institute_id',
            'inst.name as institute_name',
            'course.id as course_id',
            'course.name as course_name'
        ])
        ->from(KaabaAttendance::class, 'att')
        ->join('att.application', 'app')
        ->leftJoin('att.institute', 'inst')
        ->leftJoin('app.course', 'course')
        ->where('att.attendance_date BETWEEN :startDate AND :endDate')
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->groupBy('app.id', 'app.full_name', 'app.phone', 'inst.id', 'inst.name', 'course.id', 'course.name')
        ->orderBy('app.full_name', 'ASC');

    if ($applicantId) {
        $qb->andWhere('app.id = :applicantId')
           ->setParameter('applicantId', $applicantId);
    }
    
    if ($instituteId) {
        $qb->andWhere('inst.id = :instituteId')
           ->setParameter('instituteId', $instituteId);
    }
    
    if ($courseId) {
        $qb->andWhere('course.id = :courseId')
           ->setParameter('courseId', $courseId);
    }
    
    $user = $this->getUser();
    if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
        if (!empty($managedInstitutes)) {
            $qb->andWhere('att.institute IN (:institutes)')
               ->setParameter('institutes', $managedInstitutes);
        } else {
            return [];
        }
    }

    $results = $qb->getQuery()->getResult();
    
    $monthlyData = [];
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    $periodEnd = min($endDate, $today);
    
    // Pre-fetch all applications for excuses
    $applicationIds = array_column($results, 'application_id');
    
    foreach ($results as $result) {
        $appId = $result['application_id'];
        $application = $this->applicationRepository->find($appId);
        
        if (!$application) {
            continue;
        }
        
        // Get institute's minimum hours configuration
        $minHoursPerDay = 0;
        if ($result['institute_id']) {
            $institute = $this->instituteRepository->find($result['institute_id']);
            if ($institute && $institute->getSchoolHoursConfig()) {
                $minHoursPerDay = $institute->getSchoolHoursConfig()->getMinHoursPerDay() ?? 0;
            }
        }
        
        // Calculate required working days (INCLUDING future dates)
        $requiredDays = $this->calculateRequiredWorkingDays($startDate, $endDate);
        
        // Calculate required hours total for the month (expected hours)
        $expectedHours = $minHoursPerDay > 0 ? $requiredDays * $minHoursPerDay : 0;
        
        // Get all distinct attendance dates for this application (up to today only)
        $attendanceDates = $this->getAttendanceDatesForApplicationUpToToday($appId, $startDate, $periodEnd);
        
        // Calculate total hours worked and days present
        $totalHours = 0;
        $daysPresentUpToToday = count($attendanceDates);
        
        foreach ($attendanceDates as $date) {
            $attendance = $this->getAttendanceForDateAndApplication($date, $appId);
            $totalHours += $attendance['total_hours'] ?? 0;
        }
        
        // Get excused days for this student in the period
        $excusedDays = $this->getExcusedDaysCount($application, $startDate, $periodEnd);
        
        // Get active excuses with details
        $excuses = $this->excuseRepository->findExcusesForDateRange($application, null, $startDate, $periodEnd);
        $totalExcusedHours = 0;
        $excuseDetails = [];
        
        foreach ($excuses as $excuse) {
            if ($excuse->isIsApproved()) {
                $excuseHours = $excuse->getTotalExcusedHours();
                $totalExcusedHours += $excuseHours;
                $excuseDetails[] = [
                    'id' => $excuse->getId(),
                    'start_date' => $excuse->getStartDate(),
                    'end_date' => $excuse->getEndDate(),
                    'reason' => $excuse->getReason(),
                    'excuse_type' => $excuse->getExcuseType(),
                    'excused_hours_per_day' => $excuse->getExcusedHoursPerDay(),
                    'total_excused_days' => $excuse->getTotalExcusedDays(),
                    'total_excused_hours' => $excuseHours
                ];
            }
        }
        
        // Calculate effective days (present days + excused days)
        $effectiveDays = $daysPresentUpToToday + $excusedDays;
        
        // Calculate attendance percentage based on effective days (safe with zero check)
        $attendancePercentage = $requiredDays > 0 ? round(($effectiveDays / $requiredDays) * 100, 2) : 0;
        
        // Calculate hours percentage (safe with zero check)
        $hoursPercentage = $expectedHours > 0 ? round(($totalHours / $expectedHours) * 100, 2) : 0;
        
        // Calculate remaining required hours (excluding excused hours)
        $remainingRequiredHours = max(0, $expectedHours - $totalExcusedHours);
        $hoursStatus = $expectedHours > 0 ? ($totalHours >= $remainingRequiredHours ? 'met' : 'not_met') : 'not_applicable';
        
        $monthlyData[] = [
            'application_id' => $appId,
            'full_name' => $result['full_name'],
            'phone' => $result['phone'],
            'institute_id' => $result['institute_id'],
            'institute_name' => $result['institute_name'],
            'course_id' => $result['course_id'],
            'course_name' => $result['course_name'],
            'days_present' => $daysPresentUpToToday,
            'excused_days' => $excusedDays,
            'effective_days' => $effectiveDays,
            'total_hours' => round($totalHours, 2),
            'expected_hours' => round($expectedHours, 2),
            'hours_percentage' => $hoursPercentage,
            'remaining_required_hours' => round($remainingRequiredHours, 2),
            'hours_status' => $hoursStatus,
            'required_days' => $requiredDays,
            'min_hours_per_day' => $minHoursPerDay,
            'attendance_percentage' => $attendancePercentage,
            'excuse_details' => $excuseDetails,
            'total_excused_hours' => round($totalExcusedHours, 2),
        ];
    }
    
    // Sort by attendance percentage (lowest first to highlight issues)
    usort($monthlyData, function($a, $b) {
        return $a['attendance_percentage'] <=> $b['attendance_percentage'];
    });
    
    return $monthlyData;
}


private function getAttendanceDatesForApplicationUpToToday(int $applicationId, \DateTime $startDate, \DateTime $endDate): array
{
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('DISTINCT att.attendance_date')
        ->from(KaabaAttendance::class, 'att')
        ->where('att.application = :applicationId')
        ->andWhere('att.attendance_date BETWEEN :startDate AND :endDate')
        ->setParameter('applicationId', $applicationId)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->orderBy('att.attendance_date', 'ASC');
    
    $results = $qb->getQuery()->getResult();
    
    $dates = [];
    foreach ($results as $result) {
        $dates[] = $result['attendance_date'];
    }
    
    return $dates;
}

private function getAttendanceDatesForApplication(int $applicationId, \DateTime $startDate, \DateTime $endDate): array
{
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('DISTINCT att.attendance_date')
        ->from(KaabaAttendance::class, 'att')
        ->where('att.application = :applicationId')
        ->andWhere('att.attendance_date BETWEEN :startDate AND :endDate')
        ->setParameter('applicationId', $applicationId)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->orderBy('att.attendance_date', 'ASC');
    
    $results = $qb->getQuery()->getResult();
    
    $dates = [];
    foreach ($results as $result) {
        $dates[] = $result['attendance_date'];
    }
    
    return $dates;
}

private function countFutureDates(\DateTime $startDate, \DateTime $endDate): int
{
    $futureCount = 0;
    $currentDate = clone $startDate;
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    
    while ($currentDate <= $endDate) {
        if ($currentDate > $today) {
            $futureCount++;
        }
        $currentDate->modify('+1 day');
    }
    
    return $futureCount;
}

private function getMonthlyAttendanceData(\DateTime $startDate, \DateTime $endDate, $applicantId = null, $instituteId = null): array
{
    // Build query for monthly attendance - use Doctrine's functions
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select([
            'app.id as application_id',
            'app.full_name  as full_name',
            'app.phone as phone',
            'inst.id as institute_id',
            'inst.name as institute_name',
            'COUNT(DISTINCT att.attendance_date) as days_present',
            'SUM(att.total_hours) as total_hours'
        ])
        ->from(KaabaAttendance::class, 'att')
        ->join('att.application', 'app')
        ->leftJoin('att.institute', 'inst')
        ->where('att.attendance_date BETWEEN :startDate AND :endDate')
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->groupBy('app.id', 'app.full_name', 'app.phone', 'inst.id', 'inst.name')
        ->orderBy('app.full_name', 'ASC');

    // Apply filters
    if ($applicantId) {
        $qb->andWhere('app.id = :applicantId')
           ->setParameter('applicantId', $applicantId);
    }
    
    if ($instituteId) {
        $qb->andWhere('inst.id = :instituteId')
           ->setParameter('instituteId', $instituteId);
    }

    $results = $qb->getQuery()->getResult();
    
    // Now we need to get the counts for each status separately
    $statusCounts = [];
    if (!empty($results)) {
        $applicationIds = array_column($results, 'application_id');
        
        // Get present count
        $presentQb = $this->entityManager->createQueryBuilder();
        $presentQb->select('IDENTITY(att.application) as app_id', 'COUNT(att.id) as present_count')
                  ->from(KaabaAttendance::class, 'att')
                  ->where('att.attendance_date BETWEEN :startDate AND :endDate')
                  ->andWhere('att.status = :presentStatus')
                  ->andWhere('att.application IN (:appIds)')
                  ->setParameter('startDate', $startDate)
                  ->setParameter('endDate', $endDate)
                  ->setParameter('presentStatus', 'present')
                  ->setParameter('appIds', $applicationIds)
                  ->groupBy('att.application');
        
        $presentResults = $presentQb->getQuery()->getResult();
        foreach ($presentResults as $present) {
            $statusCounts[$present['app_id']]['present'] = (int)$present['present_count'];
        }
        
        // Get absent count
        $absentQb = $this->entityManager->createQueryBuilder();
        $absentQb->select('IDENTITY(att.application) as app_id', 'COUNT(att.id) as absent_count')
                 ->from(KaabaAttendance::class, 'att')
                 ->where('att.attendance_date BETWEEN :startDate AND :endDate')
                 ->andWhere('att.status = :absentStatus')
                 ->andWhere('att.application IN (:appIds)')
                 ->setParameter('startDate', $startDate)
                 ->setParameter('endDate', $endDate)
                 ->setParameter('absentStatus', 'absent')
                 ->setParameter('appIds', $applicationIds)
                 ->groupBy('att.application');
        
        $absentResults = $absentQb->getQuery()->getResult();
        foreach ($absentResults as $absent) {
            $statusCounts[$absent['app_id']]['absent'] = (int)$absent['absent_count'];
        }
        
        // Get late count
        $lateQb = $this->entityManager->createQueryBuilder();
        $lateQb->select('IDENTITY(att.application) as app_id', 'COUNT(att.id) as late_count')
               ->from(KaabaAttendance::class, 'att')
               ->where('att.attendance_date BETWEEN :startDate AND :endDate')
               ->andWhere('att.status = :lateStatus')
               ->andWhere('att.application IN (:appIds)')
               ->setParameter('startDate', $startDate)
               ->setParameter('endDate', $endDate)
               ->setParameter('lateStatus', 'late')
               ->setParameter('appIds', $applicationIds)
               ->groupBy('att.application');
        
        $lateResults = $lateQb->getQuery()->getResult();
        foreach ($lateResults as $late) {
            $statusCounts[$late['app_id']]['late'] = (int)$late['late_count'];
        }
        
        // Get attendance dates for each application
        $datesQb = $this->entityManager->createQueryBuilder();
        $datesQb->select('IDENTITY(att.application) as app_id', 'att.attendance_date')
                ->from(KaabaAttendance::class, 'att')
                ->where('att.attendance_date BETWEEN :startDate AND :endDate')
                ->andWhere('att.application IN (:appIds)')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('appIds', $applicationIds)
                ->orderBy('att.attendance_date', 'ASC');
        
        $datesResults = $datesQb->getQuery()->getResult();
        $attendanceDates = [];
        foreach ($datesResults as $dateResult) {
            if (!isset($attendanceDates[$dateResult['app_id']])) {
                $attendanceDates[$dateResult['app_id']] = [];
            }
            $attendanceDates[$dateResult['app_id']][] = $dateResult['attendance_date']->format('Y-m-d');
        }
    }
    
    // Calculate required working days for each student
    $monthlyData = [];
    foreach ($results as $result) {
        $appId = $result['application_id'];
        
        // Get institute's minimum hours configuration
        $minHoursPerDay = 0;
        if ($result['institute_id']) {
            $institute = $this->instituteRepository->find($result['institute_id']);
            if ($institute && $institute->getSchoolHoursConfig()) {
                $minHoursPerDay = $institute->getSchoolHoursConfig()->getMinHoursPerDay() ?? 0;
            }
        }
        
        $requiredDays = $this->calculateRequiredWorkingDays($startDate, $endDate);
        
        // Calculate days with sufficient hours
        $daysWithSufficientHours = 0;
        $insufficientHoursDetails = [];
        
        if (isset($attendanceDates[$appId]) && $minHoursPerDay > 0) {
            foreach ($attendanceDates[$appId] as $dateStr) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
                if ($date) {
                    $dayAttendance = $this->getAttendanceForDateAndApplication($date, $appId);
                    $dayHours = $dayAttendance['total_hours'] ?? 0;
                    
                    if ($dayHours >= $minHoursPerDay) {
                        $daysWithSufficientHours++;
                    } else {
                        $insufficientHoursDetails[] = [
                            'date' => $date->format('Y-m-d'),
                            'hours' => $dayHours,
                            'required' => $minHoursPerDay,
                            'deficit' => $minHoursPerDay - $dayHours
                        ];
                    }
                }
            }
        }
        
        $monthlyData[] = [
            'application_id' => $appId,
            'full_name' => $result['full_name'],
            'phone' => $result['phone'],
            'institute_id' => $result['institute_id'],
            'institute_name' => $result['institute_name'],
            'days_present' => (int)$result['days_present'],
            'days_with_sufficient_hours' => $daysWithSufficientHours,
            'total_hours' => (float)$result['total_hours'],
            'present_count' => $statusCounts[$appId]['present'] ?? 0,
            'absent_count' => $statusCounts[$appId]['absent'] ?? 0,
            'late_count' => $statusCounts[$appId]['late'] ?? 0,
            'required_days' => $requiredDays,
            'min_hours_per_day' => $minHoursPerDay,
            'attendance_percentage' => $requiredDays > 0 ? round(($result['days_present'] / $requiredDays) * 100, 2) : 0,
            'sufficient_hours_percentage' => $result['days_present'] > 0 ? round(($daysWithSufficientHours / $result['days_present']) * 100, 2) : 0,
            'average_hours_per_day' => $result['days_present'] > 0 ? round($result['total_hours'] / $result['days_present'], 2) : 0,
            'insufficient_hours_details' => $insufficientHoursDetails,
            'attendance_dates' => $attendanceDates[$appId] ?? []
        ];
    }
    
    return $monthlyData;
}


private function getAttendanceForDateAndApplication(\DateTime $date, int $applicationId): array
{
    $startDate = clone $date;
    $startDate->setTime(0, 0, 0);
    
    $endDate = clone $date;
    $endDate->setTime(23, 59, 59);

    // Get all attendance records for this date and application
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('att')
        ->from(KaabaAttendance::class, 'att')
        ->where('att.attendance_date = :date')
        ->andWhere('att.application = :applicationId')
        ->orderBy('att.check_in_time', 'ASC')
        ->setParameter('date', $startDate)
        ->setParameter('applicationId', $applicationId);

    $records = $qb->getQuery()->getResult();
    
    if (empty($records)) {
        return [
            'total_hours' => 0,
            'status' => 'absent'
        ];
    }
    
    // Get first and last records
    $firstRecord = $records[0];
    $lastRecord = end($records);
    
    // Calculate hours worked
    $totalHours = 0;
    $firstCheckIn = $firstRecord->getCheckInTime();
    $lastCheckOut = $lastRecord->getCheckOutTime();
    
    if ($firstCheckIn) {
        // Use check-out time if available, otherwise use last check-in time
        $endTime = $lastCheckOut ?: $lastRecord->getCheckInTime();
        
        if ($endTime) {
            $interval = $firstCheckIn->diff($endTime);
            $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
        }
    }
    
    return [
        'total_hours' => $totalHours,
        'status' => $lastRecord->getStatus(),
        'first_check_in' => $firstCheckIn,
        'last_check_out' => $lastCheckOut
    ];
}


private function calculateRequiredWorkingDays(\DateTime $startDate, \DateTime $endDate): int
{
    $requiredDays = 0;
    $currentDate = clone $startDate;
    
    while ($currentDate <= $endDate) {
        $dayOfWeek = $currentDate->format('l');
        $isSchoolDay = $this->isSchoolDay($dayOfWeek);
        $isHoliday = $this->isHoliday($currentDate);
        
        // Count all school days that are not holidays (INCLUDING future dates)
        if ($isSchoolDay && !$isHoliday) {
            $requiredDays++;
        }
        
        $currentDate->modify('+1 day');
    }
    
    // Ensure at least 1 to avoid division by zero
    return max(1, $requiredDays);
}

private function countHolidays(\DateTime $startDate, \DateTime $endDate): int
{
    $holidayCount = 0;
    $currentDate = clone $startDate;
    
    while ($currentDate <= $endDate) {
        if ($this->isHoliday($currentDate)) {
            $holidayCount++;
        }
        $currentDate->modify('+1 day');
    }
    
    return $holidayCount;
}

private function calculateMonthlyStatistics(array $monthlyData, \DateTime $startDate, \DateTime $endDate): array
{
    $totalStudents = count($monthlyData);
    $totalRequiredDays = $this->calculateRequiredWorkingDays($startDate, $endDate);
    
    // Calculate holidays count (excluding future dates)
    $holidayCount = $this->countHolidays($startDate, $endDate);
    
    // Calculate future dates count
    $futureDatesCount = $this->countFutureDates($startDate, $endDate);
    
    $totalDaysPresent = 0;
    $totalHours = 0;
    $totalRequiredHours = 0;
    $totalExcusedDays = 0;
    $totalEffectiveDays = 0;
    
    foreach ($monthlyData as $data) {
        $totalDaysPresent += $data['days_present'] ?? 0;
        $totalHours += $data['total_hours'] ?? 0;
        $totalRequiredHours += $data['expected_hours'] ?? 0;
        $totalExcusedDays += $data['excused_days'] ?? 0;
        $totalEffectiveDays += $data['effective_days'] ?? 0;
    }
    
    // Avoid division by zero
    $averageDaysPresent = $totalStudents > 0 ? round($totalDaysPresent / $totalStudents, 2) : 0;
    $averageHours = $totalStudents > 0 ? round($totalHours / $totalStudents, 2) : 0;
    $averageEffectiveDays = $totalStudents > 0 ? round($totalEffectiveDays / $totalStudents, 2) : 0;
    
    // Calculate overall attendance percentage - SAFE with zero check
    $overallAttendancePercentage = 0;
    if ($totalStudents > 0 && $totalRequiredDays > 0) {
        $maxPossibleDays = $totalStudents * $totalRequiredDays;
        if ($maxPossibleDays > 0) {
            $overallAttendancePercentage = round(($totalEffectiveDays / $maxPossibleDays) * 100, 2);
        }
    }
    
    // Calculate overall hours percentage - SAFE with zero check
    $overallHoursPercentage = 0;
    if ($totalRequiredHours > 0) {
        $overallHoursPercentage = round(($totalHours / $totalRequiredHours) * 100, 2);
    }
    
    return [
        'total_students' => $totalStudents,
        'total_required_days' => $totalRequiredDays,
        'holiday_count' => $holidayCount,
        'future_dates_count' => $futureDatesCount,
        'total_days_present' => $totalDaysPresent,
        'total_excused_days' => $totalExcusedDays,
        'total_effective_days' => $totalEffectiveDays,
        'total_hours' => round($totalHours, 2),
        'total_required_hours' => round($totalRequiredHours, 2),
        'average_days_present' => $averageDaysPresent,
        'average_effective_days' => $averageEffectiveDays,
        'average_hours' => $averageHours,
        'overall_attendance_percentage' => $overallAttendancePercentage,
        'overall_hours_percentage' => $overallHoursPercentage,
    ];
}

#[Route('/monthly-details/{id}', name: 'app_admin_attendance_monthly_details', methods: ['GET'])]
public function monthlyDetails(int $id, Request $request): Response
{
    $application = $this->applicationRepository->find($id);
    
    if (!$application) {
        return $this->json(['error' => 'Application not found'], 404);
    }
    
    $year = $request->query->getInt('year', date('Y'));
    $month = $request->query->getInt('month', date('n'));
    
    $startDate = new \DateTime("{$year}-{$month}-01");
    $endDate = clone $startDate;
    $endDate->modify('last day of this month')->setTime(23, 59, 59);
    
    // Get current date for comparison
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    
    // Get institute
    $institute = $application->getInstitute();
    
    // Get minimum hours per day from institute configuration
    $minHoursPerDay = 0;
    if ($institute && $institute->getSchoolHoursConfig()) {
        $minHoursPerDay = $institute->getSchoolHoursConfig()->getMinHoursPerDay() ?? 0;
    }
    
    // Get ALL attendance records for the ENTIRE month for this application
    $attendanceRecords = $this->attendanceRepository->findByApplicationAndDateRange(
        $application,
        $startDate,
        $endDate
    );
    
    // Group by date
    $groupedRecords = [];
    foreach ($attendanceRecords as $record) {
        $dateKey = $record->getAttendanceDate()->format('Y-m-d');
        if (!isset($groupedRecords[$dateKey])) {
            $groupedRecords[$dateKey] = [];
        }
        $groupedRecords[$dateKey][] = $record;
    }
    
    // Create an array of all days in the month with their details
    $allDays = [];
    $currentDate = clone $startDate;
    $workingDays = 0;
    $presentDaysUpToToday = 0;
    $absentDaysUpToToday = 0;
    $totalHoursUpToToday = 0;
    
    while ($currentDate <= $endDate) {
        $dateKey = $currentDate->format('Y-m-d');
        $isFutureDate = $currentDate > $today;
        $dayOfWeek = $currentDate->format('l');
        $isWeekend = !$this->isSchoolDay($dayOfWeek);
        $isHoliday = $this->isHoliday($currentDate);
        $isWorkingDay = !$isWeekend && !$isHoliday;
        
        $attendanceForDay = null;
        $dayRecords = $groupedRecords[$dateKey] ?? [];
        
        // Find attendance for this day
        if (!empty($dayRecords)) {
            // Sort by check-in time
            usort($dayRecords, function($a, $b) {
                return $a->getCheckInTime() <=> $b->getCheckInTime();
            });
            
            $firstRecord = $dayRecords[0];
            $lastRecord = end($dayRecords);
            
            $checkInTime = $firstRecord->getCheckInTime();
            $checkOutTime = $lastRecord->getCheckOutTime();
            
            // If no check-out time, use last check-in time
            if (!$checkOutTime) {
                $checkOutTime = $lastRecord->getCheckInTime();
            }
            
            // Calculate total hours
            $totalHours = 0;
            if ($checkInTime && $checkOutTime) {
                $interval = $checkInTime->diff($checkOutTime);
                $totalHours = $interval->h + ($interval->i / 60) + ($interval->s / 3600);
            }
            
            $attendanceForDay = [
                'record' => $lastRecord,
                'check_in' => $checkInTime,
                'check_out' => $checkOutTime,
                'total_hours' => $totalHours,
                'has_multiple_checkins' => count($dayRecords) > 1,
                'checkin_count' => count($dayRecords)
            ];
        }
        
        // Determine status and styling
        if ($isFutureDate) {
            $status = 'Future';
            $statusClass = 'bg-light text-dark';
            $rowClass = 'table-light';
        } elseif ($isHoliday) {
            $status = 'Holiday';
            $statusClass = 'bg-info';
            $rowClass = 'table-info';
        } elseif ($isWeekend) {
            $status = 'Weekend';
            $statusClass = 'bg-secondary';
            $rowClass = 'table-secondary';
        } elseif ($attendanceForDay) {
            $status = ucfirst($attendanceForDay['record']->getStatus());
            $statusClass = match($attendanceForDay['record']->getStatus()) {
                'present' => 'bg-success',
                'absent' => 'bg-danger',
                'late' => 'bg-warning',
                'half-day' => 'bg-info',
                default => 'bg-success' // Default to success if status is empty or null
            };
            $rowClass = $attendanceForDay['record']->getStatus() === 'absent' ? 'table-danger' : 'table-success';
            
            // Count present days and hours (only up to today)
            if (!$isFutureDate) {
                // If there are attendance records, count it as a present day
                $presentDaysUpToToday++;
                $totalHoursUpToToday += $totalHours;
            }
        } else {
            // Only count as absent if it's NOT a future date
            if (!$isFutureDate) {
                $status = 'Absent';
                $statusClass = 'bg-danger';
                $rowClass = 'table-danger';
                $absentDaysUpToToday++;
            } else {
                $status = 'Future';
                $statusClass = 'bg-light text-dark';
                $rowClass = 'table-light';
            }
        }
        
        $allDays[] = [
            'date' => $dateKey,
            'day' => $currentDate->format('D'),
            'status' => $status,
            'status_class' => $statusClass,
            'row_class' => $rowClass,
            'check_in' => !$isFutureDate && $attendanceForDay ? 
                $attendanceForDay['check_in']->format('H:i') : '--',
            'check_out' => !$isFutureDate && $attendanceForDay ? 
                $attendanceForDay['check_out']->format('H:i') : '--',
            'hours' => $attendanceForDay ? round($attendanceForDay['total_hours'], 2) : 0,
            'is_working_day' => $isWorkingDay,
            'is_weekend' => $isWeekend,
            'is_holiday' => $isHoliday,
            'is_future_date' => $isFutureDate,
            'has_multiple_checkins' => $attendanceForDay ? $attendanceForDay['has_multiple_checkins'] : false,
            'checkin_count' => $attendanceForDay ? $attendanceForDay['checkin_count'] : 0
        ];
        
        // Count working days (INCLUDING future dates for monthly requirement)
        if ($isWorkingDay) {
            $workingDays++;
        }
        
        $currentDate->modify('+1 day');
    }
    
    // Calculate attendance percentage based on days up to today only
    $workingDaysUpToToday = 0;
    $currentDate = clone $startDate;
    while ($currentDate <= $today && $currentDate <= $endDate) {
        $dayOfWeek = $currentDate->format('l');
        $isSchoolDay = $this->isSchoolDay($dayOfWeek);
        $isHoliday = $this->isHoliday($currentDate);
        
        if ($isSchoolDay && !$isHoliday) {
            $workingDaysUpToToday++;
        }
        $currentDate->modify('+1 day');
    }
    
    $attendancePercentage = $workingDaysUpToToday > 0 ? 
        round(($presentDaysUpToToday / $workingDaysUpToToday) * 100, 2) : 0;
    
    $averageHoursPerDay = $presentDaysUpToToday > 0 ? 
        round($totalHoursUpToToday / $presentDaysUpToToday, 2) : 0;
    
    // Calculate required hours total for the month (INCLUDING future dates)
    $requiredHoursTotal = $minHoursPerDay > 0 ? $workingDays * $minHoursPerDay : 0;
    
   return $this->json([
    'application_id' => $application->getId(),
    'full_name' => $application->getFullName(),
    'institute_name' => $institute ? $institute->getName() : 'No Institute',
    'min_hours_per_day' => $minHoursPerDay,
    'working_days' => $workingDays, // TOTAL working days in month (including future)
    'working_days_up_to_today' => $workingDaysUpToToday, // Working days up to today
    'present_days' => $presentDaysUpToToday, // Only up to today
    'absent_days' => $absentDaysUpToToday, // Only up to today
    'total_hours' => round($totalHoursUpToToday, 2),
    'required_hours_total' => $requiredHoursTotal,
    'month_summary' => [
        'year' => $year,
        'month' => $month,
        'month_name' => date('F', mktime(0, 0, 0, $month, 1))
    ],
    'daily_details' => $allDays
]);}


private function getDailyAttendanceWithHoursCheck(\DateTime $date, int $applicationId, ?int $minHoursPerDay): array
{
    $startDate = clone $date;
    $startDate->setTime(0, 0, 0);
    
    $endDate = clone $date;
    $endDate->setTime(23, 59, 59);

    $qb = $this->entityManager->createQueryBuilder();
    $qb->select([
            'MIN(att.check_in_time) as first_check_in',
            'MAX(att.check_in_time) as last_check_in',
            'MAX(att.check_out_time) as last_check_out',
            'SUM(att.total_hours) as total_hours',
            'att.status'
        ])
        ->from(KaabaAttendance::class, 'att')
        ->where('att.attendance_date = :date')
        ->andWhere('att.application = :applicationId')
        ->setParameter('date', $startDate)
        ->setParameter('applicationId', $applicationId)
        ->groupBy('att.application', 'att.attendance_date');

    $result = $qb->getQuery()->getOneOrNullResult();
    
    $dayHours = $result ? (float)$result['total_hours'] : 0;
    $status = $result ? $result['status'] : 'absent';
    
    // Check if hours are sufficient
    $hasSufficientHours = true;
    if ($minHoursPerDay > 0 && $status !== 'absent') {
        $hasSufficientHours = $dayHours >= $minHoursPerDay;
    }
    
    return [
        'total_hours' => $dayHours,
        'status' => $status,
        'has_sufficient_hours' => $hasSufficientHours,
        'required_hours' => $minHoursPerDay,
        'hours_deficit' => $hasSufficientHours ? 0 : max(0, $minHoursPerDay - $dayHours)
    ];
}


#[Route('/excuse/new', name: 'app_admin_attendance_excuse_new', methods: ['GET', 'POST'])]
public function newExcuse(Request $request): Response
{
    $user = $this->getUser();
    
    // Get institutes managed by current user
    if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
        $managedInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
        if (empty($managedInstitutes)) {
            $this->addFlash('warning', 'You do not manage any institutes to create excuses.');
            return $this->redirectToRoute('app_admin_attendance_index');
        }
    } else {
        $managedInstitutes = $this->instituteRepository->findAll();
    }

    $excuse = new KaabaStudentExcuse();
    $excuse->setCreatedBy($user);
    
    $form = $this->createForm(StudentExcuseType::class, $excuse, [
        'attr' => ['class' => 'excuse-form']
    ]);
    
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {

    $excuse->setInstitute($excuse->getApplication()->getInstitute());
        // Ensure institute is set from the form
        $excuse->setCreatedAt(new \DateTime());
        
        $this->entityManager->persist($excuse);
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf(
            'Excuse created for %s from %s to %s',
            $excuse->getApplication()->getFullName(),
            $excuse->getStartDate()->format('Y-m-d'),
            $excuse->getEndDate() ? $excuse->getEndDate()->format('Y-m-d') : $excuse->getStartDate()->format('Y-m-d')
        ));
        
        return $this->redirectToRoute('app_admin_attendance_excuse_list');
    }
    
    return $this->render('admin/attendance/excuse_new.html.twig', [
        'form' => $form->createView(),
        'managedInstitutes' => $managedInstitutes,
    ]);
}

#[Route('/excuse/list', name: 'app_admin_attendance_excuse_list', methods: ['GET'])]
public function listExcuses(Request $request): Response
{
    $user = $this->getUser();
    
    // Get institutes managed by current user
    if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
        $managedInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
        $instituteIds = array_map(function($inst) { return $inst->getId(); }, $managedInstitutes);
        
        if (empty($managedInstitutes)) {
            $this->addFlash('warning', 'You do not manage any institutes to view excuses.');
            return $this->render('admin/attendance/excuse_list.html.twig', [
                'excuses' => [],
                'no_institutes' => true,
                'stats' => ['total' => 0, 'approved' => 0, 'pending' => 0],
                'managedInstitutes' => []
            ]);
        }
        
        // Get excuses for managed institutes
        $qb = $this->excuseRepository->createQueryBuilder('e')
            ->leftJoin('e.institute', 'inst')
            ->where('inst.id IN (:instituteIds)')
            ->setParameter('instituteIds', $instituteIds)
            ->orderBy('e.created_at', 'DESC');
    } else {
        // Super admin can see all
        $qb = $this->excuseRepository->createQueryBuilder('e')
            ->orderBy('e.created_at', 'DESC');
    }
    
    // Apply filters
    $status = $request->query->get('status');
    if ($status === 'approved') {
        $qb->andWhere('e.is_approved = true');
    } elseif ($status === 'pending') {
        $qb->andWhere('e.is_approved = false');
    }
    
    $instituteFilter = $request->query->get('institute');
    if ($instituteFilter) {
        $qb->andWhere('e.institute = :instituteFilter')
           ->setParameter('instituteFilter', $instituteFilter);
    }
    
    $excuses = $qb->getQuery()->getResult();
    
    // Calculate statistics
    $stats = [
        'total' => count($excuses),
        'approved' => count(array_filter($excuses, fn($e) => $e->isIsApproved())),
        'pending' => count(array_filter($excuses, fn($e) => !$e->isIsApproved())),
    ];
    
    return $this->render('admin/attendance/excuse_list.html.twig', [
        'excuses' => $excuses,
        'stats' => $stats,
        'no_institutes' => false,
        'managedInstitutes' => $managedInstitutes ?? $this->instituteRepository->findAll(),
        'current_status_filter' => $status,
        'current_institute_filter' => $instituteFilter,
    ]);
}

#[Route('/excuse/{id}/confirm', name: 'app_admin_attendance_excuse_confirm', methods: ['GET', 'POST'])]
public function confirmExcuse(int $id, Request $request): Response
{
    $excuse = $this->excuseRepository->find($id);
    
    if (!$excuse) {
        $this->addFlash('error', 'Excuse not found.');
        return $this->redirectToRoute('app_admin_attendance_excuse_list');
    }
    
    $user = $this->getUser();
    
    // Check permission
    $institute = $excuse->getInstitute();
    $userInstitutes = $this->instituteRepository->findBy(['manager' => $user]);
    $isManager = false;
    foreach ($userInstitutes as $userInstitute) {
        if ($userInstitute->getId() === $institute->getId()) {
            $isManager = true;
            break;
        }
    }
    
    if (!in_array('ROLE_SUPER_ADMIN', $user->getRoles()) && !$isManager) {
        $this->addFlash('error', 'You do not have permission to confirm this excuse.');
        return $this->redirectToRoute('app_admin_attendance_excuse_list');
    }
    
    $form = $this->createForm(ExcuseConfirmationType::class, $excuse);
    $form->handleRequest($request);
    
    if ($form->isSubmitted() && $form->isValid()) {
        if ($excuse->isIsApproved()) {
            $excuse->setApprovedBy($user);
            $excuse->setApprovedAt(new \DateTime());
        }
        $excuse->setUpdatedAt(new \DateTime());
        
        $this->entityManager->flush();
        
        $status = $excuse->isIsApproved() ? 'approved' : 'rejected';
        $this->addFlash('success', sprintf('Excuse has been %s successfully.', $status));
        
        return $this->redirectToRoute('app_admin_attendance_excuse_list');
    }
    
    // Get attendance records for the excuse period
    $attendanceRecords = [];
    $excuseDates = $excuse->getExcuseDates();
    foreach ($excuseDates as $date) {
        $attendance = $this->attendanceRepository->findAttendanceByApplicationAndDate(
            $excuse->getApplication()->getId(),
            $date
        );
        if ($attendance) {
            $attendanceRecords[$date->format('Y-m-d')] = $attendance;
        }
    }
    
    return $this->render('admin/attendance/excuse_confirm.html.twig', [
        'excuse' => $excuse,
        'form' => $form->createView(),
        'attendanceRecords' => $attendanceRecords,
    ]);
}

#[Route('/excuse/{id}/delete', name: 'app_admin_attendance_excuse_delete', methods: ['POST'])]
public function deleteExcuse(int $id, Request $request): Response
{
    $excuse = $this->excuseRepository->find($id);
    
    if (!$excuse) {
        $this->addFlash('error', 'Excuse not found.');
        return $this->redirectToRoute('app_admin_attendance_excuse_list');
    }
    
    if ($this->isCsrfTokenValid('delete_excuse_' . $excuse->getId(), $request->request->get('_token'))) {
        $this->entityManager->remove($excuse);
        $this->entityManager->flush();
        $this->addFlash('success', 'Excuse deleted successfully.');
    } else {
        $this->addFlash('error', 'Invalid CSRF token.');
    }
    
    return $this->redirectToRoute('app_admin_attendance_excuse_list');
}

// Helper method to check if a student is excused on a specific date
private function isStudentExcused(KaabaApplication $application, \DateTimeInterface $date): ?KaabaStudentExcuse
{
    $excuses = $this->excuseRepository->findActiveExcusesForDate($application, $date);
    return !empty($excuses) ? $excuses[0] : null;
}

// Helper method to get total excused days for a student in a period
private function getExcusedDaysCount(KaabaApplication $application, \DateTimeInterface $startDate, \DateTimeInterface $endDate): int
{
    return $this->excuseRepository->findExcusedDaysCountForPeriod($application, $startDate, $endDate);
}

// Also add this helper method if needed:
private function getExcusedHoursForPeriod(KaabaApplication $application, \DateTimeInterface $startDate, \DateTimeInterface $endDate): float
{
    return $this->excuseRepository->findExcusedHoursForPeriod($application, $startDate, $endDate);
}


#[Route('/students-by-institute/{instituteId}', name: 'app_admin_attendance_students_by_institute', methods: ['GET'])]
public function getStudentsByInstitute(int $instituteId): JsonResponse
{
    $students = $this->applicationRepository->createQueryBuilder('app')
        ->leftJoin('app.studentDevice', 'sd')
        ->where('app.institute = :instituteId')
        ->andWhere('sd IS NOT NULL')
        ->setParameter('instituteId', $instituteId)
        ->orderBy('app.full_name', 'ASC')
        ->getQuery()
        ->getResult();
    
    $studentArray = [];
    foreach ($students as $student) {
        $studentArray[] = [
            'id' => $student->getId(),
            'full_name' => $student->getFullName(),
            'phone' => $student->getPhone(),
        ];
    }
    
    return $this->json(['students' => $studentArray]);
}

}