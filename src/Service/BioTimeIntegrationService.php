<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Entity\KaabaAttendance;
use App\Entity\KaabaApplication;
use App\Entity\KaabaBiotimeArea;
use App\Entity\KaabaBiotimeDevice;
use App\Entity\KaabaStudentDevice;
use App\Entity\KaabaConfigSchoolDay;
use Doctrine\ORM\EntityManagerInterface;

class BioTimeIntegrationService
{
    private BioTimeService $bioTimeService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        BioTimeService $bioTimeService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->bioTimeService = $bioTimeService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * Check if BioTime API is reachable
     */
    public function isApiReachable(): bool
    {
        return $this->bioTimeService->isReachable();
    }

    /**
     * Test authentication with BioTime API
     */
    public function testAuthentication(): array
    {
        try {
            $token = $this->bioTimeService->authenticate();
            return [
                'success' => true,
                'message' => 'Authentication successful',
                'base_url' => $this->bioTimeService->getBaseUrl()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync BioTime areas to local database
     */
   /**
 * Sync BioTime areas to local database
 */
public function syncAreas(): array
{
    $result = [
        'success' => false,
        'synced' => 0,
        'updated' => 0,
        'errors' => [],
        'debug' => []
    ];

    try {
        // First, test authentication
        if (!$this->bioTimeService->authenticate()) {
            throw new \RuntimeException('Unable to authenticate with BioTime API');
        }

        // Get areas from BioTime with debugging
        $result['debug']['api_call'] = 'Calling getAllAreas()';
        $areas = $this->bioTimeService->getAllAreas();
        
        $result['debug']['raw_response'] = $areas;
        
        // Check different response formats
        if (empty($areas)) {
            throw new \RuntimeException('BioTime API returned empty response');
        }

        // Try different data structures
        $areaData = [];
        
        if (isset($areas['data']) && is_array($areas['data'])) {
            // Format 1: {data: [...]}
            $areaData = $areas['data'];
            $result['debug']['format'] = 'data_key_format';
        } elseif (isset($areas['results']) && is_array($areas['results'])) {
            // Format 2: {results: [...]} (common in paginated APIs)
            $areaData = $areas['results'];
            $result['debug']['format'] = 'results_key_format';
        } elseif (is_array($areas) && isset($areas[0])) {
            // Format 3: Direct array
            $areaData = $areas;
            $result['debug']['format'] = 'direct_array_format';
        } else {
            // Try to extract any array-like structure
            foreach ($areas as $key => $value) {
                if (is_array($value) && isset($value[0])) {
                    $areaData = $value;
                    $result['debug']['format'] = "nested_array_format:{$key}";
                    break;
                }
            }
        }

        $result['debug']['area_data_count'] = count($areaData);
        $result['debug']['area_data_sample'] = !empty($areaData) ? array_slice($areaData, 0, 3) : [];

        if (empty($areaData)) {
            throw new \RuntimeException('No area data found in API response. Check API endpoint.');
        }

        foreach ($areaData as $index => $areaItem) {
            try {
                $result['debug']['processing_item_' . $index] = $areaItem;
                
                // Try different field names for area ID and name
                $areaId = null;
                $areaName = null;
                
                // Common field names for ID
                $idFields = ['id', 'area_id', 'areaId', 'areaID', 'area'];
                foreach ($idFields as $field) {
                    if (isset($areaItem[$field])) {
                        $areaId = $areaItem[$field];
                        break;
                    }
                }
                
                // Common field names for name
                $nameFields = ['name', 'area_name', 'areaName', 'title', 'desc', 'description'];
                foreach ($nameFields as $field) {
                    if (isset($areaItem[$field])) {
                        $areaName = $areaItem[$field];
                        break;
                    }
                }

                if (!$areaId) {
                    $result['errors'][] = [
                        'item_index' => $index,
                        'error' => 'No area ID found',
                        'data' => $areaItem
                    ];
                    continue;
                }

                if (!$areaName) {
                    // Try to generate a name from ID if none found
                    $areaName = 'Area ' . $areaId;
                }

                // Convert areaId to string
                $areaId = (string)$areaId;

                // Check if area already exists
                $existingArea = $this->entityManager->getRepository(KaabaBiotimeArea::class)
                    ->findOneBy(['area_id' => $areaId]);

                if ($existingArea) {
                    // Update existing area
                    $existingArea->setAreaName($areaName);
                    
                    // Update description if available
                    if (isset($areaItem['desc']) || isset($areaItem['description'])) {
                        $description = $areaItem['desc'] ?? $areaItem['description'];
                        $existingArea->setDescription($description);
                    }
                    
                    // Update other fields if available
                    if (isset($areaItem['timezone'])) {
                        $existingArea->setTimezone($areaItem['timezone']);
                    }
                    
                    $existingArea->setUpdatedAt(new \DateTime());
                    $result['updated']++;
                    
                    $result['debug']['updated_area_' . $areaId] = 'Updated: ' . $areaName;
                } else {
                    // Create new area
                    $area = new KaabaBiotimeArea();
                    $area->setAreaId($areaId);
                    $area->setAreaName($areaName);
                    
                    // Set description
                    $description = $areaItem['desc'] ?? $areaItem['description'] ?? null;
                    $area->setDescription($description);
                    
                    // Set timezone if available
                    if (isset($areaItem['timezone'])) {
                        $area->setTimezone($areaItem['timezone']);
                    }
                    
                    // Set created_at
                    $area->setCreatedAt(new \DateTime());
                    
                    $this->entityManager->persist($area);
                    $result['synced']++;
                    
                    $result['debug']['created_area_' . $areaId] = 'Created: ' . $areaName;
                }
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'item_index' => $index,
                    'area_id' => $areaId ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }

        $this->entityManager->flush();
        $result['success'] = true;
        $result['message'] = "Synced {$result['synced']} new areas, updated {$result['updated']} areas";

    } catch (\Exception $e) {
        $result['message'] = 'Failed to sync areas: ' . $e->getMessage();
        $result['debug']['exception'] = [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ];
        $this->logger->error('BioTime area sync failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    return $result;
}

    /**
     * Sync BioTime devices to local database
     */
    public function syncDevices(): array
    {
        $result = [
            'success' => false,
            'synced' => 0,
            'updated' => 0,
            'errors' => []
        ];

        try {
            // Note: BioTime API might not have a direct devices endpoint
            // This would need to be implemented based on your BioTime API documentation
            // For now, let's assume we can get devices from areas
            
            $areas = $this->entityManager->getRepository(KaabaBiotimeArea::class)->findAll();
            
            foreach ($areas as $area) {
                try {
                    // This is a placeholder - you'll need to implement the actual device sync
                    // based on your BioTime API endpoints
                    
                    // Example: Get devices for each area
                    // $devices = $this->bioTimeService->request('GET', "areas/{$area->getAreaId()}/devices/");
                    
                    // For now, we'll create a dummy device for demonstration
                    $device = new KaabaBiotimeDevice();
                    $device->setDeviceId('device-' . $area->getAreaId());
                    $device->setDeviceName('Device for ' . $area->getAreaName());
                    $device->setDeviceType('biometric');
                    $device->setStatus('active');
                    $device->setArea($area);
                    $device->setLocation('Main Entrance');
                    
                    $this->entityManager->persist($device);
                    $result['synced']++;
                    
                } catch (\Exception $e) {
                    $result['errors'][] = [
                        'area_id' => $area->getAreaId(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->entityManager->flush();
            $result['success'] = true;
            $result['message'] = "Synced {$result['synced']} devices";

        } catch (\Exception $e) {
            $result['message'] = 'Failed to sync devices: ' . $e->getMessage();
            $this->logger->error('BioTime device sync failed', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
 * Generate truly unique employee code for BioTime
 */
private function generateEmployeeCode(KaabaApplication $application): string
{
    // Method 1: Use application ID + timestamp + random
    $baseCode = 'STU' . $application->getId() . 'T' . time() . 'R' . rand(100, 999);
    
    // Make sure it's not too long (BioTime might have limits)
    $code = substr($baseCode, 0, 20);
    
    $this->logger->info('Generated employee code', [
        'code' => $code,
        'application_id' => $application->getId(),
        'student_name' => $application->getFullName()
    ]);
    
    return $code;
}
   
/**
 * Enroll a student in BioTime system
 */
public function enrollStudentInBioTime(KaabaApplication $application, KaabaBiotimeArea $area): array
{
    $result = [
        'success' => false,
        'message' => '',
        'bio_time_data' => null
    ];

    try {
        // Check if student is already enrolled locally
        $existingEnrollment = $this->entityManager->getRepository(KaabaStudentDevice::class)
            ->findOneBy(['application' => $application]);

        if ($existingEnrollment && $existingEnrollment->getBiotimeEmployeeId()) {
            return [
                'success' => true,
                'message' => 'Student already enrolled in BioTime',
                'bio_time_data' => [
                    'employee_id' => $existingEnrollment->getBiotimeEmployeeId()
                ]
            ];
        }

        // Generate TRULY UNIQUE employee code
        $empCode = $this->generateEmployeeCode($application);
        
        // Double-check this code doesn't exist in BioTime (just in case)
        if ($this->bioTimeService->checkEmployeeExistsInBioTime($empCode)) {
            // If by some miracle it exists, generate another one
            $this->logger->warning('Generated code already exists, generating another', [
                'original_code' => $empCode,
                'application_id' => $application->getId()
            ]);
            $empCode = 'STU' . $application->getId() . 'T' . microtime(true) . 'R' . rand(1000, 9999);
            $empCode = substr($empCode, 0, 20);
        }
        
        // Prepare employee data for BioTime
        $employeeData = $this->prepareStudentForBioTime($application, $area, $empCode);
        
        $this->logger->info('Attempting to enroll student with unique code', [
            'application_id' => $application->getId(),
            'emp_code' => $empCode,
            'student_name' => $application->getFullName(),
            'employee_data' => $employeeData
        ]);
        
        // Create employee in BioTime
        $bioTimeResponse = $this->bioTimeService->createEmployeeInBioTime($employeeData);
        
        $this->logger->info('BioTime API response', [
            'response' => $bioTimeResponse,
            'emp_code' => $empCode
        ]);
        
        // Verify we got a valid response
        if (empty($bioTimeResponse) || !isset($bioTimeResponse['id'])) {
            if (isset($bioTimeResponse['error']) || isset($bioTimeResponse['detail'])) {
                $errorMsg = $bioTimeResponse['error'] ?? $bioTimeResponse['detail'] ?? 'Unknown error';
                throw new \RuntimeException('BioTime API error: ' . $errorMsg);
            }
            throw new \RuntimeException('BioTime API returned invalid response: ' . json_encode($bioTimeResponse));
        }
        
        // Get the new employee ID
        $employeeId = (string)$bioTimeResponse['id'];
        
        // Verify the returned employee code matches what we sent
        $returnedEmpCode = $bioTimeResponse['emp_code'] ?? '';
        if ($returnedEmpCode !== $empCode) {
            $this->logger->warning('Employee code mismatch', [
                'sent_code' => $empCode,
                'returned_code' => $returnedEmpCode,
                'employee_id' => $employeeId
            ]);
        }
        
        // Verify this is a NEW employee (check if name matches)
        $returnedFirstName = $bioTimeResponse['first_name'] ?? '';
        $returnedLastName = $bioTimeResponse['last_name'] ?? '';
        $expectedFirstName = $employeeData['first_name'];
        $expectedLastName = $employeeData['last_name'];
        
        if ($returnedFirstName !== $expectedFirstName || $returnedLastName !== $expectedLastName) {
            $this->logger->error('Created employee name mismatch!', [
                'expected' => $expectedFirstName . ' ' . $expectedLastName,
                'received' => $returnedFirstName . ' ' . $returnedLastName,
                'emp_code' => $empCode,
                'employee_id' => $employeeId,
                'warning' => 'BioTime might have updated an existing employee instead of creating new!'
            ]);
            
            // This is a critical error - BioTime updated wrong employee
            throw new \RuntimeException(sprintf(
                'BioTime created/updated wrong employee! Expected "%s %s" but got "%s %s". ' .
                'Employee ID: %s, Code: %s. Check if emp_code was duplicate.',
                $expectedFirstName, $expectedLastName,
                $returnedFirstName, $returnedLastName,
                $employeeId, $empCode
            ));
        }
        
        // Create or update student device record
        $studentDevice = $existingEnrollment ?? new KaabaStudentDevice();
        $studentDevice->setApplication($application);
        $studentDevice->setEnrollmentStatus('enrolled');
        $studentDevice->setEnrollmentDate(new \DateTime());
        
        // Get first device from area
        $devices = $area->getDevices();
        if ($devices->count() > 0) {
            $studentDevice->setDevice($devices->first());
        }
        
        $studentDevice->setBiotimeEmployeeId($employeeId);
        $studentDevice->setBiotimeEnrollmentResponse(json_encode($bioTimeResponse));
        
        $this->entityManager->persist($studentDevice);
        $this->entityManager->flush();

        $result['success'] = true;
        $result['message'] = 'Student enrolled in BioTime successfully';
        $result['bio_time_data'] = $bioTimeResponse;

        $this->logger->info('Student enrolled in BioTime successfully', [
            'application_id' => $application->getId(),
            'employee_code' => $empCode,
            'employee_id' => $employeeId,
            'area_id' => $area->getAreaId(),
            'student_name' => $application->getFullName()
        ]);

    } catch (\Exception $e) {
        $result['message'] = 'Failed to enroll student in BioTime: ' . $e->getMessage();
        $this->logger->error('BioTime student enrollment failed', [
            'application_id' => $application->getId(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    return $result;
}
    /**
     * Prepare student data for BioTime enrollment
     */
   
/**
 * Prepare student data for BioTime enrollment
 */
private function prepareStudentForBioTime(KaabaApplication $application, KaabaBiotimeArea $area, string $empCode): array
{
    // Get the BioTime area ID
    $areaId = $area->getAreaId();
    
    if (!$areaId) {
        throw new \RuntimeException('Area ID is empty for area: ' . $area->getAreaName());
    }

    // Extract names
    $firstName = $this->extractFirstName($application->getFullName());
    $lastName = $this->extractLastName($application->getFullName());
    
    // Prepare employee data
    $employeeData = [
        'emp_code' => $empCode,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'nickname' => $application->getFullName(),
        'department' => 2, // Using department ID 4 (Admin and finance)
        'area' => [(int)$areaId],
        'verify_mode' => 0,
        'mobile' => $application->getPhone() ?? '',
        'email' => $application->getEmail() ?? '',
        'app_status' => 0
    ];
    
    // Add hire_date
    $hireDate = $application->getAcceptedDate();
    if ($hireDate) {
        $employeeData['hire_date'] = $hireDate->format('Y-m-d');
    } else {
        $employeeData['hire_date'] = (new \DateTime())->format('Y-m-d');
    }
    
    $this->logger->info('Prepared employee data for BioTime', [
        'emp_code' => $empCode,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'application_id' => $application->getId()
    ]);
    
    return $employeeData;
}

/**
 * Generate unique employee code that doesn't exist in BioTime
 */

private function generateUniqueEmployeeCode(KaabaApplication $application): string
{
    // Try different strategies for generating unique codes
    $strategies = [
        // Strategy 1: Use application ID with prefix
        function() use ($application) {
            return 'STU' . str_pad($application->getId(), 6, '0', STR_PAD_LEFT);
        },
        
        // Strategy 2: Use UUID without dashes
        function() use ($application) {
            $uuid = str_replace('-', '', $application->getUuid());
            return 'U' . substr($uuid, 0, 7);
        },
        
        // Strategy 3: Use timestamp + application ID
        function() use ($application) {
            return 'T' . time() . 'A' . $application->getId();
        },
        
        // Strategy 4: Use phone number if available
        function() use ($application) {
            $phone = $application->getPhone();
            if ($phone) {
                // Extract numbers from phone
                $numbers = preg_replace('/[^0-9]/', '', $phone);
                if (strlen($numbers) >= 4) {
                    return 'P' . substr($numbers, -4);
                }
            }
            return null;
        },
        
        // Strategy 5: Use first letters of name + application ID
        function() use ($application) {
            $name = $application->getFullName();
            $initials = '';
            $words = explode(' ', $name);
            foreach ($words as $word) {
                if (!empty($word)) {
                    $initials .= strtoupper(substr($word, 0, 1));
                }
            }
            if (strlen($initials) > 0) {
                return $initials . str_pad($application->getId(), 4, '0', STR_PAD_LEFT);
            }
            return null;
        }
    ];
    
    $maxAttempts = 20;
    $attempts = 0;
    $generatedCodes = [];
    
    $this->logger->info('Starting unique employee code generation', [
        'application_id' => $application->getId(),
        'student_name' => $application->getFullName()
    ]);
    
    while ($attempts < $maxAttempts) {
        // Try each strategy in order
        foreach ($strategies as $strategyIndex => $strategy) {
            $code = $strategy();
            
            // Skip if strategy returned null
            if ($code === null) {
                continue;
            }
            
            // Ensure code is string and not too long (BioTime might have limits)
            $code = substr((string)$code, 0, 15);
            
            // Check if we already tried this code
            if (in_array($code, $generatedCodes)) {
                continue;
            }
            
            $generatedCodes[] = $code;
            $attempts++;
            
            $this->logger->info('Trying employee code', [
                'attempt' => $attempts,
                'strategy' => $strategyIndex + 1,
                'code' => $code,
                'application_id' => $application->getId()
            ]);
            
            // Check if code exists in BioTime
            if (!$this->bioTimeService->checkEmployeeExistsInBioTime($code)) {
                $this->logger->info('Found unique employee code', [
                    'code' => $code,
                    'application_id' => $application->getId(),
                    'total_attempts' => $attempts
                ]);
                return $code;
            }
            
            // If we've reached max attempts, break
            if ($attempts >= $maxAttempts) {
                break 2; // Break out of both loops
            }
        }
    }
    
    // If we can't find a unique code, generate a random one
    $this->logger->warning('Could not find unique code with strategies, generating random', [
        'application_id' => $application->getId(),
        'attempts' => $attempts
    ]);
    
    // Generate random code as last resort
    $randomCode = 'R' . time() . rand(1000, 9999);
    
    // Double-check this random code doesn't exist
    if (!$this->bioTimeService->checkEmployeeExistsInBioTime($randomCode)) {
        return $randomCode;
    }
    
    // If even random code exists, throw specific error
    throw new \RuntimeException(sprintf(
        'Unable to generate unique employee code after %d attempts. ' .
        'All generated codes already exist in BioTime. ' .
        'Application ID: %d, Student: %s',
        $attempts,
        $application->getId(),
        $application->getFullName()
    ));
}
    /**
     * Extract first name from full name
     */
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', $fullName);
        return $parts[0] ?? $fullName;
    }

    /**
     * Extract last name from full name
     */
    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', $fullName);
        return count($parts) > 1 ? end($parts) : '';
    }

    /**
     * Map gender to BioTime format
     */
    /**
 * Map gender to BioTime format
 */
private function mapGender($gender): string
{
    // Handle null case
    if (!$gender) {
        return 'Male'; // Default
    }
    
    // If it's a KaabaGender entity, get the name
    if ($gender instanceof \App\Entity\KaabaGender) {
        $genderString = $gender->getName(); // Assuming getName() method exists
    } else {
        $genderString = (string)$gender;
    }
    
    $genderString = strtolower($genderString);
    return str_contains($genderString, 'female') ? 'Female' : 'Male';
}

    /**
     * Sync attendance data from BioTime
     */
    public function syncAttendanceForArea(KaabaBiotimeArea $area, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $result = [
            'success' => false,
            'synced' => 0,
            'errors' => [],
            'message' => ''
        ];

        try {
            // Get enrolled students for this area's institute
            $institute = $area->getInstitute();
            if (!$institute) {
                throw new \RuntimeException('Area is not associated with any institute');
            }

            $studentDevices = $this->entityManager->getRepository(KaabaStudentDevice::class)
                ->findEnrolledStudentsByInstitute($institute->getId());

            // Create a mapping of BioTime employee ID to KaabaApplication
            $employeeMapping = [];
            foreach ($studentDevices as $studentDevice) {
                if ($bioTimeId = $studentDevice->getBiotimeEmployeeId()) {
                    $employeeMapping[$bioTimeId] = $studentDevice->getApplication();
                }
            }

            // Get attendance transactions from BioTime
            // Note: This endpoint might vary based on your BioTime setup
            $attendanceData = $this->bioTimeService->request('GET', 'iclock/transactions/', [
                'start_time' => $startDate->format('Y-m-d H:i:s'),
                'end_time' => $endDate->format('Y-m-d H:i:s'),
                'area_id' => $area->getAreaId()
            ]);

            $transactions = $attendanceData['data'] ?? $attendanceData;

            foreach ($transactions as $transaction) {
                try {
                    $employeeId = $transaction['emp_code'] ?? $transaction['employee_id'] ?? null;
                    if (!$employeeId || !isset($employeeMapping[$employeeId])) {
                        continue;
                    }

                    $application = $employeeMapping[$employeeId];

                    // Check if attendance already exists for this transaction
                    $existingAttendance = $this->entityManager->getRepository(KaabaAttendance::class)
                        ->findOneBy(['biotime_transaction_id' => $transaction['id']]);

                    if ($existingAttendance) {
                        // Update existing attendance
                        $this->updateAttendanceFromTransaction($existingAttendance, $transaction);
                        $result['synced']++;
                    } else {
                        // Create new attendance
                        $attendance = $this->createAttendanceFromTransaction($application, $transaction, $area);
                        $this->entityManager->persist($attendance);
                        $result['synced']++;
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = [
                        'transaction_id' => $transaction['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->entityManager->flush();
            $result['success'] = true;
            $result['message'] = "Synced {$result['synced']} attendance records";

        } catch (\Exception $e) {
            $result['message'] = 'Failed to sync attendance: ' . $e->getMessage();
            $this->logger->error('BioTime attendance sync failed', [
                'area_id' => $area->getAreaId(),
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Create attendance record from BioTime transaction
     */
    private function createAttendanceFromTransaction(
        KaabaApplication $application, 
        array $transaction,
        KaabaBiotimeArea $area
    ): KaabaAttendance {
        $attendance = new KaabaAttendance();
        $attendance->setApplication($application);
        
        // Get device from transaction
        $deviceId = $transaction['device_id'] ?? null;
        if ($deviceId) {
            $device = $this->entityManager->getRepository(KaabaBiotimeDevice::class)
                ->findOneBy(['device_id' => (string)$deviceId]);
            if ($device) {
                $attendance->setDevice($device);
            }
        }
        
        $attendance->setCheckInTime(new \DateTime($transaction['punch_time']));
        $attendance->setAttendanceDate(new \DateTime($transaction['punch_time']));
        $attendance->setBiotimeTransactionId($transaction['id']);
        $attendance->setAttendanceType('biometric');
        $attendance->setStatus($this->determineAttendanceStatus($attendance, $area));
        
        return $attendance;
    }

    /**
     * Update existing attendance record
     */
    private function updateAttendanceFromTransaction(KaabaAttendance $attendance, array $transaction): void
    {
        // Update check-out time if this is a second punch
        if (!$attendance->getCheckOutTime() && $attendance->getCheckInTime()) {
            $punchTime = new \DateTime($transaction['punch_time']);
            if ($punchTime > $attendance->getCheckInTime()) {
                $attendance->setCheckOutTime($punchTime);
                $attendance->calculateTotalHours();
                $attendance->setUpdatedAt(new \DateTime());
            }
        }
    }

    /**
     * Determine attendance status based on check-in time and institute configuration
     */
    private function determineAttendanceStatus(KaabaAttendance $attendance, KaabaBiotimeArea $area): string
    {
        $checkInTime = $attendance->getCheckInTime();
        $config = $area->getWorkingHoursConfig();
        
        if (!$config) {
            return 'present'; // Default status
        }

        // Check if it's a holiday
        if ($this->isHoliday($attendance->getAttendanceDate(), $area)) {
            return 'holiday';
        }

        // Check if it's a school day
        if (!$this->isSchoolDay($attendance->getAttendanceDate())) {
            return 'absent'; // Or 'off_day' depending on your logic
        }

        // Check if late (if expected start time is configured)
        // This would need your business logic for determining lateness
        
        return 'present';
    }

    /**
     * Check if date is a holiday
     */
    private function isHoliday(\DateTimeInterface $date, KaabaBiotimeArea $area): bool
    {
        $holidayConfig = $area->getHolidayConfig();
        if (!$holidayConfig) {
            return false;
        }

        // Check if the date matches the holiday
        $holidayDate = $holidayConfig->getDate();
        if ($holidayDate && $holidayDate->format('Y-m-d') === $date->format('Y-m-d')) {
            return true;
        }

        // Check if recurring holiday
        if ($holidayConfig->isIsRecurring()) {
            $holidayMonthDay = $holidayDate->format('m-d');
            $checkMonthDay = $date->format('m-d');
            return $holidayMonthDay === $checkMonthDay;
        }

        return false;
    }

    /**
     * Check if date is a school day
     */
    private function isSchoolDay(\DateTimeInterface $date): bool
    {
        $dayOfWeek = $date->format('l'); // Monday, Tuesday, etc.
        
        $schoolDay = $this->entityManager->getRepository(KaabaConfigSchoolDay::class)
            ->findOneBy(['dayOfWeek' => $dayOfWeek]);
            
        return $schoolDay ? $schoolDay->isIsSchoolDay() : true; // Default to true
    }

    /**
     * Get BioTime API status and statistics
     */
    public function getApiStatus(): array
    {
        try {
            $testResult = $this->testAuthentication();
            
            $areasCount = $this->entityManager->getRepository(KaabaBiotimeArea::class)
                ->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $devicesCount = $this->entityManager->getRepository(KaabaBiotimeDevice::class)
                ->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $enrolledStudents = $this->entityManager->getRepository(KaabaStudentDevice::class)
                ->createQueryBuilder('sd')
                ->select('COUNT(sd.id)')
                ->where('sd.enrollment_status IN (:statuses)')
                ->setParameter('statuses', ['enrolled', 'active'])
                ->getQuery()
                ->getSingleScalarResult();

            return [
                'success' => true,
                'api_status' => $testResult,
                'local_data' => [
                    'areas' => (int)$areasCount,
                    'devices' => (int)$devicesCount,
                    'enrolled_students' => (int)$enrolledStudents
                ],
                'last_sync' => [
                    // You could add last sync timestamps here
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}