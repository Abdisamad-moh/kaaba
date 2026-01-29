<?php
// src/Controller/Admin/AttendanceController.php

namespace App\Controller;

use App\Entity\KaabaInstitute;
use App\Entity\KaabaAttendance;
use App\Entity\KaabaApplication;
use App\Form\AttendanceSearchType;
use App\Entity\KaabaConfigSchoolDay;
use App\Entity\KaabaConfigSchoolHour;
use App\Entity\KaabaConfigSchoolHoliday;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\KaabaInstituteRepository;
use App\Repository\KaabaAttendanceRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\KaabaApplicationRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\KaabaConfigSchoolDayRepository;
use App\Repository\KaabaConfigSchoolHourRepository;
use Symfony\Component\Validator\Constraints\DateTime;
use App\Repository\KaabaConfigSchoolHolidayRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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

    public function __construct(
        EntityManagerInterface $entityManager,
        KaabaAttendanceRepository $attendanceRepository,
        KaabaApplicationRepository $applicationRepository,
        KaabaInstituteRepository $instituteRepository,
        KaabaConfigSchoolDayRepository $schoolDayRepository,
        KaabaConfigSchoolHolidayRepository $holidayRepository,
        KaabaConfigSchoolHourRepository $schoolHourRepository
    ) {
        $this->entityManager = $entityManager;
        $this->attendanceRepository = $attendanceRepository;
        $this->applicationRepository = $applicationRepository;
        $this->instituteRepository = $instituteRepository;
        $this->schoolDayRepository = $schoolDayRepository;
        $this->holidayRepository = $holidayRepository;
        $this->schoolHourRepository = $schoolHourRepository;
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
            'institutes' => $managedInstitutes
        ]);

        $searchForm->handleRequest($request);
        
        // Get filter values
        $date = $searchForm->get('date')->getData() ?? new \DateTime();
        $applicant = $searchForm->get('applicant')->getData();
        $institute = $searchForm->get('institute')->getData();
        $status = $searchForm->get('status')->getData();
        
        // Convert date to string for template
        $dateString = $date->format('Y-m-d');
        
        // Check if it's a holiday
        $isHoliday = $this->isHoliday($date);
        
        // Check if it's a school day
        $dayOfWeek = $date->format('l');
        $isSchoolDay = $this->isSchoolDay($dayOfWeek);
        
        // Get attendance data
        $attendanceData = $this->getDailyAttendance($date, $applicant, $institute, $status);
        
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
            'no_institutes' => false
        ]);
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
            'total_employees' => count($attendanceData),
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'half_day' => 0,
            'verified' => 0,
            'total_hours' => 0,
        ];

        foreach ($attendanceData as $data) {
            $stats['total_hours'] += $data['total_hours'] ?? 0;
            
            if ($data['is_verified']) {
                $stats['verified']++;
            }

            switch ($data['status']) {
                case 'present':
                    $stats['present']++;
                    break;
                case 'late':
                    $stats['late']++;
                    break;
                case 'absent':
                    $stats['absent']++;
                    break;
                case 'half-day':
                    $stats['half_day']++;
                    break;
            }
        }

        $stats['attendance_rate'] = $stats['total_employees'] > 0 ? 
            (($stats['present'] + $stats['late'] + $stats['half_day']) / $stats['total_employees']) * 100 : 0;

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
                'stats' => []
            ]);
        }
    } else {
        $managedInstitutes = $this->instituteRepository->findAll();
    }

    // ALWAYS default to current month/year on initial load
    $currentYear = date('Y');
    $currentMonth = date('n');
    
    // Get filter parameters
    $year = $request->query->getInt('year', $currentYear);
    $month = $request->query->getInt('month', $currentMonth);
    $monthYear = $request->query->get('monthYear');
    
    // If monthYear is provided, use it (for the month picker)
    if ($monthYear) {
        $dateParts = explode('-', $monthYear);
        if (count($dateParts) === 2) {
            $year = (int)$dateParts[0];
            $month = (int)$dateParts[1];
        }
    }
    
    $applicantId = $request->query->get('applicant');
    $instituteId = $request->query->get('institute');
    
    // Validate month and year
    if ($month < 1 || $month > 12) {
        $month = $currentMonth;
    }
    if ($year < $currentYear - 5 || $year > $currentYear) {
        $year = $currentYear;
    }
    
    $startDate = new \DateTime("{$year}-{$month}-01");
    $endDate = clone $startDate;
    $endDate->modify('last day of this month')->setTime(23, 59, 59);
    
    // Get monthly data with required hours calculation
    $monthlyData = $this->getMonthlyAttendanceDataWithRequiredHours($startDate, $endDate, $applicantId, $instituteId);
    
    // Calculate statistics
    $stats = $this->calculateMonthlyStatistics($monthlyData, $startDate, $endDate);
    
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    // Create search form for applicant autocomplete
    $searchForm = $this->createForm(AttendanceSearchType::class, null, [
        'method' => 'GET',
        'csrf_protection' => false,
        'institutes' => $managedInstitutes
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
        'managedInstitutes' => $managedInstitutes,
        'searchForm' => $searchForm->createView()  // Add this line
    ]);
}






private function getMonthlyAttendanceDataWithRequiredHours(\DateTime $startDate, \DateTime $endDate, $applicantId = null, $instituteId = null): array
{
    // Build query for monthly attendance
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select([
            'app.id as application_id',
            'app.full_name as full_name',
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
    
    // Get attendance dates for each application
    $attendanceDates = [];
    if (!empty($results)) {
        $applicationIds = array_column($results, 'application_id');
        
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
        foreach ($datesResults as $dateResult) {
            $appId = $dateResult['app_id'];
            if (!isset($attendanceDates[$appId])) {
                $attendanceDates[$appId] = [];
            }
            $attendanceDates[$appId][] = $dateResult['attendance_date']->format('Y-m-d');
        }
    }
    
    // Calculate required working days
    $totalRequiredDays = $this->calculateRequiredWorkingDays($startDate, $endDate);
    
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
        
        // Calculate required hours (required days * min hours per day)
        $requiredHours = $minHoursPerDay > 0 ? $totalRequiredDays * $minHoursPerDay : 0;
        
        // Calculate days with sufficient hours
        $daysWithSufficientHours = 0;
        if (isset($attendanceDates[$appId]) && $minHoursPerDay > 0) {
            foreach ($attendanceDates[$appId] as $dateStr) {
                $date = \DateTime::createFromFormat('Y-m-d', $dateStr);
                if ($date) {
                    $dayAttendance = $this->getAttendanceForDateAndApplication($date, $appId);
                    $dayHours = $dayAttendance['total_hours'] ?? 0;
                    
                    if ($dayHours >= $minHoursPerDay) {
                        $daysWithSufficientHours++;
                    }
                }
            }
        }
        
        // Calculate hours percentage
        $hoursPercentage = 0;
        if ($requiredHours > 0) {
            $hoursPercentage = round(($result['total_hours'] / $requiredHours) * 100, 2);
        }
        
        // Calculate sufficient hours percentage
        $sufficientHoursPercentage = 0;
        if ($result['days_present'] > 0 && $minHoursPerDay > 0) {
            $sufficientHoursPercentage = round(($daysWithSufficientHours / $result['days_present']) * 100, 2);
        }
        
        // Determine status based on attendance percentage
        $attendancePercentage = $totalRequiredDays > 0 ? round(($result['days_present'] / $totalRequiredDays) * 100, 2) : 0;
        $status = 'No Data';
        $statusClass = 'bg-secondary';
        
        if ($attendancePercentage >= 80) {
            $status = 'Good';
            $statusClass = 'bg-success';
        } elseif ($attendancePercentage >= 60) {
            $status = 'Average';
            $statusClass = 'bg-warning';
        } elseif ($attendancePercentage > 0) {
            $status = 'Poor';
            $statusClass = 'bg-danger';
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
            'required_days' => $totalRequiredDays,
            'required_hours' => $requiredHours,
            'min_hours_per_day' => $minHoursPerDay,
            'hours_percentage' => $hoursPercentage,
            'sufficient_hours_percentage' => $sufficientHoursPercentage,
            'attendance_percentage' => $attendancePercentage,
            'average_hours_per_day' => $result['days_present'] > 0 ? round($result['total_hours'] / $result['days_present'], 2) : 0,
            'status' => $status,
            'status_class' => $statusClass,
            'attendance_dates' => $attendanceDates[$appId] ?? []
        ];
    }
    
    return $monthlyData;
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
        ->groupBy('att.application', 'att.attendance_date', 'att.status'); // Added att.status to GROUP BY

    $result = $qb->getQuery()->getOneOrNullResult();
    
    return $result ?: [
        'total_hours' => 0,
        'status' => 'absent'
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
        
        // Count only school days that are not holidays
        if ($isSchoolDay && !$isHoliday) {
            $requiredDays++;
        }
        
        $currentDate->modify('+1 day');
    }
    
    return $requiredDays;
}




private function calculateMonthlyStatistics(array $monthlyData, \DateTime $startDate, \DateTime $endDate): array
{
    $totalStudents = count($monthlyData);
    $totalRequiredDays = $this->calculateRequiredWorkingDays($startDate, $endDate);
    
    $totalDaysPresent = 0;
    $totalHours = 0;
    $totalRequiredHours = 0;
    $totalDaysWithSufficientHours = 0;
    $studentsWithHoursConfig = 0;
    
    foreach ($monthlyData as $data) {
        $totalDaysPresent += $data['days_present'];
        $totalHours += $data['total_hours'];
        $totalRequiredHours += $data['required_hours'];
        
        // Count students with hours configuration
        if ($data['min_hours_per_day'] > 0) {
            $studentsWithHoursConfig++;
        }
        
        // Count days with sufficient hours
        if (isset($data['days_with_sufficient_hours'])) {
            $totalDaysWithSufficientHours += $data['days_with_sufficient_hours'];
        }
    }
    
    $averageDaysPresent = $totalStudents > 0 ? round($totalDaysPresent / $totalStudents, 2) : 0;
    $averageHours = $totalStudents > 0 ? round($totalHours / $totalStudents, 2) : 0;
    $averageRequiredHours = $totalStudents > 0 ? round($totalRequiredHours / $totalStudents, 2) : 0;
    
    // Calculate overall hours completion percentage
    $overallHoursPercentage = 0;
    if ($totalRequiredHours > 0) {
        $overallHoursPercentage = round(($totalHours / $totalRequiredHours) * 100, 2);
    }
    
    // Calculate hours compliance rate
    $hoursComplianceRate = 0;
    if ($totalDaysPresent > 0 && $studentsWithHoursConfig > 0) {
        $hoursComplianceRate = round(($totalDaysWithSufficientHours / $totalDaysPresent) * 100, 2);
    }
    
    return [
        'total_students' => $totalStudents,
        'total_required_days' => $totalRequiredDays,
        'total_days_present' => $totalDaysPresent,
        'total_hours' => round($totalHours, 2),
        'total_required_hours' => round($totalRequiredHours, 2),
        'average_days_present' => $averageDaysPresent,
        'average_hours' => $averageHours,
        'average_required_hours' => $averageRequiredHours,
        'overall_hours_percentage' => $overallHoursPercentage,
        'students_with_hours_config' => $studentsWithHoursConfig,
        'total_days_with_sufficient_hours' => $totalDaysWithSufficientHours,
        'hours_compliance_rate' => $hoursComplianceRate
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
    
    while ($currentDate <= $endDate) {
        $dateKey = $currentDate->format('Y-m-d');
        $isFutureDate = $currentDate > $today;
        $dayOfWeek = $currentDate->format('l');
        $isWeekend = !$this->isSchoolDay($dayOfWeek);
        $isHoliday = $this->isHoliday($currentDate);
        $isWorkingDay = !$isWeekend && !$isHoliday;
        
        $attendanceForDay = null;
        $dayRecords = $groupedRecords[$dateKey] ?? [];
        
        // Find attendance for this day - use the latest check-in as check-out
        if (!empty($dayRecords)) {
            // Sort by check-in time
            usort($dayRecords, function($a, $b) {
                return $a->getCheckInTime() <=> $b->getCheckInTime();
            });
            
            $firstRecord = $dayRecords[0];
            $lastRecord = end($dayRecords);
            
            // Use the last check-in time as check-out time if no check-out exists
            $checkInTime = $firstRecord->getCheckInTime();
            $checkOutTime = $lastRecord->getCheckOutTime();
            
            // If no check-out time, use the last check-in time as virtual check-out
            if (!$checkOutTime) {
                $checkOutTime = $lastRecord->getCheckInTime();
            }
            
            // Calculate total hours using the virtual check-out
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
                default => 'bg-secondary'
            };
            $rowClass = $attendanceForDay['record']->getStatus() === 'absent' ? 'table-danger' : 'table-success';
        } else {
            $status = 'Absent';
            $statusClass = 'bg-danger';
            $rowClass = 'table-danger';
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
        
        // Count working days (excluding future dates)
        if ($isWorkingDay && !$isFutureDate) {
            $workingDays++;
        }
        
        $currentDate->modify('+1 day');
    }
    
    // Calculate statistics (excluding future dates)
    $presentDays = count(array_filter($allDays, function($day) {
        return !$day['is_future_date'] && $day['is_working_day'] && in_array($day['status'], ['Present', 'Late', 'Half-day']);
    }));
    
    $absentDays = count(array_filter($allDays, function($day) {
        return !$day['is_future_date'] && $day['is_working_day'] && $day['status'] === 'Absent';
    }));
    
    $totalHours = array_sum(array_column($allDays, 'hours'));
    
    $attendancePercentage = $workingDays > 0 ? round(($presentDays / $workingDays) * 100, 2) : 0;
    $averageHoursPerDay = $presentDays > 0 ? round($totalHours / $presentDays, 2) : 0;
    
    return $this->json([
        'application_id' => $application->getId(),
        'full_name' => $application->getFullName(),
        'institute_name' => $institute ? $institute->getName() : 'No Institute',
        'working_days' => $workingDays,
        'present_days' => $presentDays,
        'absent_days' => $absentDays,
        'attendance_percentage' => $attendancePercentage,
        'total_hours' => round($totalHours, 2),
        'average_hours_per_day' => $averageHoursPerDay,
        'month_summary' => [
            'year' => $year,
            'month' => $month,
            'month_name' => date('F', mktime(0, 0, 0, $month, 1))
        ],
        'daily_details' => $allDays
    ]);
}


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

}