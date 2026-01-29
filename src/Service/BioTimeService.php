<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class BioTimeService
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;
    private array $credentials;
    private ?string $token = null;

    private LoggerInterface $logger;


    public function __construct(
       HttpClientInterface $httpClient,
    string $bioTimeBaseUrl,
    string $bioTimeUsername,
    string $bioTimePassword,
    LoggerInterface $logger
    ) {
        $this->httpClient = HttpClient::create([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
        
        // Ensure the base URL has a protocol
        $this->baseUrl = rtrim($bioTimeBaseUrl, '/');
        if (!preg_match('#^https?://#', $this->baseUrl)) {
            $this->baseUrl = 'http://' . $this->baseUrl;
        }
        
        $this->credentials = [
            'username' => $bioTimeUsername,
            'password' => $bioTimePassword,
        ];

        $this->logger = $logger;
    }

    public function getTransactions(array $params = []): array
{
    // Ensure authentication
    if (!$this->token && !$this->authenticate()) {
        throw new \RuntimeException('Unable to authenticate with BioTime API');
    }

    $url = $this->baseUrl . '/iclock/api/transactions/';

    $options = [
        'headers' => [
            'Authorization' => 'JWT ' . $this->token,
            'Accept' => 'application/json',
        ],
        'query' => $params,
    ];

    $this->logger->info('Fetching BioTime transactions', [
        'url' => $url,
        'params' => $params,
    ]);

    $response = $this->httpClient->request('GET', $url, $options);

    $statusCode = $response->getStatusCode();
    $content = $response->getContent(false);

    if ($statusCode >= 200 && $statusCode < 300) {
        return json_decode($content, true) ?? [];
    }

    $this->logger->error('Failed to fetch transactions', [
        'status_code' => $statusCode,
        'response' => $content,
    ]);

    return [];
}

public function getAttendanceTransactions(
    ?string $from = null,
    ?string $to = null
): array {
    $transactions = [];
    $page = 1;
    $pageSize = 100;

    do {
        $params = [
            'page'      => $page,
            'page_size' => $pageSize,
        ];

        if ($from) {
            $params['start_time'] = $from . ' 00:00:00';
        }

        if ($to) {
            $params['end_time'] = $to . ' 23:59:59';
        }

        $response = $this->getTransactions($params);

        if (!is_array($response) || !isset($response['data'])) {
            break;
        }

        $transactions = array_merge($transactions, $response['data']);

        $hasNext = !empty($response['next']);
        $page++;

    } while ($hasNext);

    $this->logger->info('BioTime attendance transactions fetched', [
        'count' => count($transactions),
        'from'  => $from,
        'to'    => $to,
    ]);

    return $transactions;
}



    /**
     * Authenticate with BioTime API and get JWT token
     */
    public function authenticate(): bool
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/jwt-api-token-auth/', [
                'json' => $this->credentials,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode === 200) {
                $data = json_decode($content, true);
                $this->token = $data['token'] ?? null;
                
                if ($this->token) {
                    $this->logger->info('BioTime API JWT authentication successful');
                    return true;
                } else {
                    $this->logger->error('BioTime API authentication failed - no token in response', [
                        'response' => $content
                    ]);
                }
            } else {
                $this->logger->error('BioTime API authentication failed with status code', [
                    'status_code' => $statusCode,
                    'response' => $content
                ]);
            }
            
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('BioTime API connection failed', [
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('BioTime authentication error', [
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Make authenticated request to BioTime API
     */
   /**
 * Make authenticated request to BioTime API
 */
public function request(string $method, string $endpoint, array $data = []): array
{
    // Ensure we have a valid token
    if (!$this->token && !$this->authenticate()) {
        throw new \RuntimeException('Unable to authenticate with BioTime API');
    }

    $url = $this->baseUrl . '/personnel/api/' . ltrim($endpoint, '/');
    
    $options = [
        'headers' => [
            'Authorization' => 'JWT ' . $this->token,
            'Content-Type' => 'application/json',
        ],
    ];

    if (!empty($data)) {
        if ($method === 'GET') {
            $options['query'] = $data;
        } else {
            $options['json'] = $data;
        }
    }

    try {
        // Log the request details for debugging
        $this->logger->info('BioTime API request', [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'has_token' => !empty($this->token)
        ]);

        $response = $this->httpClient->request($method, $url, $options);
        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        // Log response details
        $this->logger->info('BioTime API response', [
            'method' => $method,
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 500) // First 500 chars
        ]);

        if ($statusCode === 401) {
            // Token might be expired, try to reauthenticate once
            $this->token = null;
            if ($this->authenticate()) {
                $options['headers']['Authorization'] = 'JWT ' . $this->token;
                $response = $this->httpClient->request($method, $url, $options);
                $statusCode = $response->getStatusCode();
                $content = $response->getContent(false);
            }
        }

        if ($statusCode >= 200 && $statusCode < 300) {
            $decoded = json_decode($content, true);
            
            // Log decoded response
            $this->logger->info('BioTime API decoded response', [
                'endpoint' => $endpoint,
                'decoded' => $decoded,
                'is_array' => is_array($decoded),
                'is_null' => is_null($decoded)
            ]);
            
            // Handle empty or null responses
            if ($decoded === null || $decoded === '') {
                return [];
            }
            
            return $decoded ?? [];
        } else {
            $this->logger->error('BioTime API request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'url' => $url,
                'status_code' => $statusCode,
                'response' => $content
            ]);
            
            // Don't throw for 404, return empty array
            if ($statusCode === 404) {
                return [];
            }
            
            throw new \RuntimeException(sprintf(
                'BioTime API request failed with status %d: %s',
                $statusCode,
                $content
            ));
        }

    } catch (TransportExceptionInterface $e) {
        $this->logger->error('BioTime API request transport error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'url' => $url,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw new \RuntimeException('BioTime API connection error: ' . $e->getMessage());
    } catch (\Exception $e) {
        $this->logger->error('BioTime API request general error', [
            'method' => $method,
            'endpoint' => $endpoint,
            'url' => $url,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

    /**
     * Employee Methods
     */
    
    public function getEmployees(array $params = []): array
    {
        return $this->request('GET', 'employees/', $params);
    }

    public function getEmployee(int $id): array
    {
        // Try different endpoint formats
        $endpoints = [
            "employees/{$id}/",
            "employees/{$id}",
            "employees/?id={$id}",
            "employees/?emp_code={$id}",
        ];

        foreach ($endpoints as $endpoint) {
            $result = $this->request('GET', $endpoint);
            
            // If we get a successful response with data, return it
            if (!empty($result)) {
                if (isset($result['data']) && !empty($result['data'])) {
                    return $result['data'][0] ?? $result['data'];
                }
                if (isset($result['id'])) {
                    return $result;
                }
                if (is_array($result) && !isset($result['detail'])) {
                    return $result;
                }
            }
        }

        return [];
    }

    /**
     * Get employee by code (more robust version)
     */
    public function getEmployeeByCode(string $empCode): ?array
    {
        try {
            // Try different search approaches
            $endpoints = [
                "employees/?emp_code={$empCode}",
                "employees/?search={$empCode}",
                "employees/?q={$empCode}"
            ];
            
            foreach ($endpoints as $endpoint) {
                try {
                    $response = $this->request('GET', $endpoint);
                    
                    // Check different response formats
                    if (isset($response['data']) && is_array($response['data']) && count($response['data']) > 0) {
                        return $response['data'][0];
                    }
                    
                    if (isset($response['results']) && is_array($response['results']) && count($response['results']) > 0) {
                        return $response['results'][0];
                    }
                    
                    if (is_array($response) && isset($response[0]['emp_code']) && $response[0]['emp_code'] === $empCode) {
                        return $response[0];
                    }
                    
                    // If we get a single employee object
                    if (isset($response['emp_code']) && $response['emp_code'] === $empCode) {
                        return $response;
                    }
                    
                } catch (\Exception $e) {
                    continue; // Try next endpoint
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Error getting employee by code', [
                'emp_code' => $empCode,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function createEmployee(array $data): array
    {
        return $this->request('POST', 'employees/', $data);
    }

    public function updateEmployee(int $id, array $data): array
    {
        $endpoints = [
            "employees/{$id}/",
            "employees/{$id}",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                return $this->request('PUT', $endpoint, $data);
            } catch (\Exception $e) {
                // Try next endpoint
                continue;
            }
        }

        throw new \RuntimeException('Unable to update employee: all endpoint formats failed');
    }

    public function deleteEmployee(int $id): array
    {
        $endpoints = [
            "employees/{$id}/",
            "employees/{$id}",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                return $this->request('DELETE', $endpoint);
            } catch (\Exception $e) {
                // Try next endpoint
                continue;
            }
        }

        throw new \RuntimeException('Unable to delete employee: all endpoint formats failed');
    }

    /**
     * Bulk Employee Operations
     */
    
    public function adjustEmployeeArea(array $data): array
    {
        return $this->request('POST', 'employees/adjust_area/', $data);
    }

    public function adjustEmployeeDepartment(array $data): array
    {
        return $this->request('POST', 'employees/adjust_department/', $data);
    }

    public function resignEmployee(array $data): array
    {
        return $this->request('POST', 'employees/adjust_regsin/', $data);
    }

    public function deleteEmployeeBioTemplate(array $data): array
    {
        return $this->request('POST', 'employees/del_bio_template/', $data);
    }

    public function resyncEmployeeToDevice(array $data): array
    {
        return $this->request('POST', 'employees/resync_to_device/', $data);
    }

    /**
     * Department and Area Methods
     */
    
    public function getDepartments(): array
    {
        return $this->request('GET', 'departments/');
    }

    public function getAreas(): array
    {
        return $this->request('GET', 'areas/');
    }

    /**
     * Get specific area by ID
     */
    public function getArea(int $id): array
    {
        $endpoints = [
            "areas/{$id}/",
            "areas/{$id}",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $result = $this->request('GET', $endpoint);
                
                // If we get a successful response with data, return it
                if (!empty($result)) {
                    if (isset($result['data']) && !empty($result['data'])) {
                        return $result['data'][0] ?? $result['data'];
                    }
                    if (isset($result['id'])) {
                        return $result;
                    }
                    if (is_array($result) && !isset($result['detail'])) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                // Try next endpoint if this one fails
                continue;
            }
        }

        throw new \RuntimeException("Area with ID {$id} not found");
    }

    /**
     * Get specific department by ID
     */
    public function getDepartment(int $id): array
    {
        $endpoints = [
            "departments/{$id}/",
            "departments/{$id}",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $result = $this->request('GET', $endpoint);
                
                // If we get a successful response with data, return it
                if (!empty($result)) {
                    if (isset($result['data']) && !empty($result['data'])) {
                        return $result['data'][0] ?? $result['data'];
                    }
                    if (isset($result['id'])) {
                        return $result;
                    }
                    if (is_array($result) && !isset($result['detail'])) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                // Try next endpoint if this one fails
                continue;
            }
        }

        throw new \RuntimeException("Department with ID {$id} not found");
    }

    /**
     * Get specific position by ID
     */
    public function getPosition(int $id): array
    {
        $endpoints = [
            "positions/{$id}/",
            "positions/{$id}",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                $result = $this->request('GET', $endpoint);
                
                // If we get a successful response with data, return it
                if (!empty($result)) {
                    if (isset($result['data']) && !empty($result['data'])) {
                        return $result['data'][0] ?? $result['data'];
                    }
                    if (isset($result['id'])) {
                        return $result;
                    }
                    if (is_array($result) && !isset($result['detail'])) {
                        return $result;
                    }
                }
            } catch (\Exception $e) {
                // Try next endpoint if this one fails
                continue;
            }
        }

        throw new \RuntimeException("Position with ID {$id} not found");
    }

    /**
     * Check if service is reachable
     */
    public function isReachable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/jwt-api-token-auth/', [
                'timeout' => 10,
            ]);
            
            return $response->getStatusCode() !== 404;
        } catch (\Exception $e) {
            $this->logger->warning('BioTime API health check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get employee with full details including relationships
     */
    public function getEmployeeWithDetails(int $id): array
    {
        $employee = $this->getEmployee($id);
        
        // If we have department ID, get department details
        if (isset($employee['department']['id'])) {
            $departmentId = $employee['department']['id'];
            try {
                $employee['department_details'] = $this->getDepartment($departmentId);
            } catch (\Exception $e) {
                // Department might not exist or we don't have access
                $this->logger->warning('Could not fetch department details', [
                    'department_id' => $departmentId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Process areas if they exist
        if (isset($employee['area']) && is_array($employee['area'])) {
            foreach ($employee['area'] as &$area) {
                if (isset($area['id'])) {
                    try {
                        $area['details'] = $this->getArea($area['id']);
                    } catch (\Exception $e) {
                        $this->logger->warning('Could not fetch area details', [
                            'area_id' => $area['id'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return $employee;
    }

    /**
     * Sync all employees from BioTime
     */
    public function syncAllEmployees(array $params = []): array
    {
        $employees = $this->getEmployees($params);
        $synced = [];
        
        if (isset($employees['data']) && is_array($employees['data'])) {
            foreach ($employees['data'] as $employeeData) {
                $synced[] = $this->syncEmployee($employeeData);
            }
        }
        
        return $synced;
    }

    /**
     * Sync single employee from BioTime data
     */
    public function syncEmployee(array $employeeData): array
    {
        // This would be implemented in a synchronization service
        // For now, return the processed data
        return [
            'bio_time_id' => $employeeData['id'],
            'emp_code' => $employeeData['emp_code'],
            'name' => $employeeData['full_name'] ?? $employeeData['format_name'],
            'department' => $employeeData['department'] ?? null,
            'areas' => $employeeData['area'] ?? [],
            'sync_timestamp' => time()
        ];
    }

    /**
     * Enhanced method to get employee with resolved relationships
     */
    public function getEmployeeWithResolvedRelations(int $id): array
    {
        $employee = $this->getEmployee($id);
        
        // Resolve department
        if (isset($employee['department']['id'])) {
            try {
                $departmentId = $employee['department']['id'];
                $employee['department_resolved'] = $this->getDepartment($departmentId);
            } catch (\Exception $e) {
                $this->logger->warning('Could not resolve department', [
                    'department_id' => $departmentId,
                    'error' => $e->getMessage()
                ]);
                $employee['department_resolved'] = null;
            }
        }
        
        // Resolve areas
        if (isset($employee['area']) && is_array($employee['area'])) {
            foreach ($employee['area'] as $key => $area) {
                if (isset($area['id'])) {
                    try {
                        $employee['area'][$key]['resolved'] = $this->getArea($area['id']);
                    } catch (\Exception $e) {
                        $this->logger->warning('Could not resolve area', [
                            'area_id' => $area['id'],
                            'error' => $e->getMessage()
                        ]);
                        $employee['area'][$key]['resolved'] = null;
                    }
                }
            }
        }
        
        // Resolve position if exists
        if (isset($employee['position']['id'])) {
            try {
                $positionId = $employee['position']['id'];
                $employee['position_resolved'] = $this->getPosition($positionId);
            } catch (\Exception $e) {
                $this->logger->warning('Could not resolve position', [
                    'position_id' => $positionId,
                    'error' => $e->getMessage()
                ]);
                $employee['position_resolved'] = null;
            }
        }
        
        return $employee;
    }

    /**
     * Get all areas with pagination
     */
    public function getAllAreas(array $params = []): array
    {
        return $this->request('GET', 'areas/', $params);
    }

    /**
     * Get all departments with pagination
     */
    public function getAllDepartments(array $params = []): array
    {
        return $this->request('GET', 'departments/', $params);
    }

    /**
     * Get all positions with pagination
     */
    public function getAllPositions(array $params = []): array
    {
        return $this->request('GET', 'positions/', $params);
    }

    /**
     * Get all employees with pagination
     */
    public function getAllEmployees(array $params = []): array
    {
        return $this->request('GET', 'employees/', $params);
    }

    /**
     * Sync all reference data (areas, departments, positions)
     */
    public function syncReferenceData(): array
    {
        $result = [
            'areas' => [],
            'departments' => [],
            'positions' => []
        ];
        
        try {
            // Sync areas
            $areas = $this->getAllAreas();
            $result['areas'] = $areas['data'] ?? $areas;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync areas', ['error' => $e->getMessage()]);
            $result['areas_error'] = $e->getMessage();
        }
        
        try {
            // Sync departments
            $departments = $this->getAllDepartments();
            $result['departments'] = $departments['data'] ?? $departments;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync departments', ['error' => $e->getMessage()]);
            $result['departments_error'] = $e->getMessage();
        }
        
        try {
            // Sync positions
            $positions = $this->getAllPositions();
            $result['positions'] = $positions['data'] ?? $positions;
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync positions', ['error' => $e->getMessage()]);
            $result['positions_error'] = $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Create a new employee in BioTime
     */
   /**
 * Create a new employee in BioTime
 */
/**
 * Create a new employee in BioTime
 */
public function createEmployeeInBioTime(array $employeeData): array
{
    try {
        $this->logger->info('Creating employee in BioTime', [
            'employee_data' => $employeeData
        ]);
        
        // Call the API
        $response = $this->request('POST', 'employees/', $employeeData);
        
        $this->logger->info('BioTime create employee response', [
            'response' => $response,
            'response_type' => gettype($response),
            'is_array' => is_array($response),
            'count' => is_array($response) ? count($response) : 0,
            'emp_code' => $employeeData['emp_code'] ?? 'unknown'
        ]);
        
        // If response is empty array, try to check if employee was created
        if (empty($response) || (is_array($response) && count($response) === 0)) {
            // Try to get the employee to see if it was created
            $empCode = $employeeData['emp_code'] ?? null;
            if ($empCode) {
                sleep(1); // Wait a moment for the API to process
                $createdEmployee = $this->getEmployeeByCode($empCode);
                if ($createdEmployee) {
                    return $createdEmployee;
                }
            }
            
            // If we still have empty response, return a specific error
            return [
                'success' => false,
                'message' => 'BioTime API returned empty response. Employee may not have been created.',
                'empty_response' => true
            ];
        }
        
        // Return the raw response
        return $response;
        
    } catch (\Exception $e) {
        $this->logger->error('Error creating employee in BioTime', [
            'employee_data' => $employeeData,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'message' => 'API Error: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * Update an existing employee in BioTime
     */
    /**
 * Update an existing employee in BioTime
 */
public function updateEmployeeInBioTime(int $employeeId, array $employeeData): array
{
    try {
        $this->logger->info('Updating employee in BioTime', [
            'employee_id' => $employeeId,
            'employee_data' => $employeeData
        ]);
        
        $response = $this->request('PUT', "employees/{$employeeId}/", $employeeData);
        
        $this->logger->info('BioTime update employee response', [
            'response' => $response,
            'employee_id' => $employeeId
        ]);
        
        // Return the raw response
        return $response;
        
    } catch (\Exception $e) {
        $this->logger->error('Error updating employee in BioTime', [
            'employee_id' => $employeeId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'message' => 'API Error: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

    /**
     * Delete an employee from BioTime
     */
    public function deleteEmployeeFromBioTime(int $bioTimeId): array
    {
        $endpoints = [
            "employees/{$bioTimeId}/",
            "employees/{$bioTimeId}",
        ];

        foreach ($endpoints as $endpoint) {
            try {
                return $this->request('DELETE', $endpoint);
            } catch (\Exception $e) {
                // Try next endpoint if this one fails
                continue;
            }
        }

        throw new \RuntimeException('Unable to delete employee from BioTime: all endpoint formats failed');
    }

    /**
     * Prepare employee data for BioTime API format
     */
    private function prepareEmployeeDataForBioTime(array $employeeData): array
    {
        $bioTimeData = [];
        
        // Required fields
        if (isset($employeeData['emp_code'])) {
            $bioTimeData['emp_code'] = $employeeData['emp_code'];
        }
        
        if (isset($employeeData['department'])) {
            $bioTimeData['department'] = $employeeData['department'];
        }
        
        if (isset($employeeData['area'])) {
            $bioTimeData['area'] = $employeeData['area'];
        }
        
        // Optional fields
        $optionalFields = [
            'first_name', 'last_name', 'nickname', 'device_password', 'card_no',
            'position', 'hire_date', 'gender', 'birthday', 'verify_mode', 'emp_type',
            'contact_tel', 'office_tel', 'mobile', 'national', 'city', 'address',
            'postcode', 'email', 'enroll_sn', 'ssn', 'religion', 'app_status'
        ];
        
        foreach ($optionalFields as $field) {
            if (isset($employeeData[$field]) && $employeeData[$field] !== null) {
                $bioTimeData[$field] = $employeeData[$field];
            }
        }
        
        // Handle date formatting
        if (isset($employeeData['hire_date']) && $employeeData['hire_date'] instanceof \DateTime) {
            $bioTimeData['hire_date'] = $employeeData['hire_date']->format('Y-m-d');
        }
        
        if (isset($employeeData['birthday']) && $employeeData['birthday'] instanceof \DateTime) {
            $bioTimeData['birthday'] = $employeeData['birthday']->format('Y-m-d');
        }
        
        return $bioTimeData;
    }

   /**
 * Check if employee exists in BioTime by employee code
 */
public function checkEmployeeExistsInBioTime(string $empCode): bool
{
    try {
        $this->logger->info('Checking if employee exists', ['emp_code' => $empCode]);
        
        $employee = $this->getEmployeeByCode($empCode);
        
        $exists = !empty($employee);
        
        $this->logger->info('Employee existence check result', [
            'emp_code' => $empCode,
            'exists' => $exists,
            'employee_data' => $employee
        ]);
        
        return $exists;
        
    } catch (\Exception $e) {
        $this->logger->error('Error checking if employee exists in BioTime', [
            'emp_code' => $empCode,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

    /**
     * Get employee from BioTime by employee code
     */
    public function getEmployeeByCodeFromBioTime(string $empCode): ?array
    {
        return $this->getEmployeeByCode($empCode);
    }
}