<?php
// src/Service/SmsAPIService.php
// src/Service/SmsAPIService.php

// src/Service/SmsAPIService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface; // <-- use this
use Symfony\Contracts\Cache\ItemInterface;

class SmsAPIService
{
    private string $username = '634025322';
    private string $password = '@#634025322#';
    private string $authUrl = 'https://somteloss.com/externalresource/Authentication/authlogin';
    private string $apiUrl;
    private CacheInterface $cache;
    private string $bearerToken = '634025322_AjEicZxA7QDEV6e7dGMNVQtA1Sl9EuHyJ5v0TSVpdrk=';

    public function __construct(
        string $sometelSMSAPI,
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
        CacheInterface  $cache
    ) {
        $this->apiUrl = $sometelSMSAPI;
        $this->cache = $cache;
    }

    public function send(string $phoneNumber, string $type, array $params = []): bool
    {
        $phone = $this->normalizePhoneNumber($phoneNumber);
        if (!$phone) {
            // throw new \InvalidArgumentException('Invalid phone number format.');
            return false;
        }

        $message = $this->buildMessage($type, $params);
        if (!$message) {
            // throw new \InvalidArgumentException("Missing or invalid parameters for message type: $type");
            return false;
        }

        $token = $this->getToken();

        $payload = [
            'reciever' => $phone,
            'body' => $message,
        ];

        // dd($this->apiUrl);

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 10,
                'max_duration' => 15,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode !== 200) {
                // throw new \RuntimeException("SMS API returned status $statusCode: $content");
            }

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("Failed to send SMS", ['error' => $e->getMessage()]);
            // throw new \RuntimeException('Failed to send SMS: ' . $e->getMessage());
            return false;
        }
    }

    private function getToken(): string
    {
        return $this->cache->get('somtel_sms_token', function (ItemInterface $item) {
            $item->expiresAfter(86400); // 24 hours

            $response = $this->httpClient->request('POST', $this->authUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => $this->username,
                    'Password' => $this->password, // Capital "P"
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
            $this->logger->error('SMS Auth Response', ['status' => $statusCode, 'body' => $content]);

            if ($statusCode !== 200) {
                // throw new \RuntimeException('Failed to authenticate with SMS API.');
            }

            $data = json_decode($content, true);

            if (!isset($data['token'])) {
                // throw new \RuntimeException('Token not found in login response.');
            }

            return $data['token'];
        });
    }

    private function normalizePhoneNumber(string $number): ?string
    {
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '0')) {
            $number = substr($number, 1);
        }
        return strlen($number) === 9 ? $number : null;
    }

    private function buildMessage(string $type, array $params): ?string
{
    return match ($type) {
        'job_alert' => "New Job Alert!  A new job that matches with your preferred job categories has just been posted! 
Explore more details at https://www.metier-quest.com/jobs",
        'internal_job' => $this->buildInternalJobMessage($params),
        'interview' => $this->buildInterviewJobMessage($params),
        default => null,
    };
}


private function buildInternalJobMessage(array $params): ?string
{
    if (!isset($params['company'], $params['title'])) {
        return null;
    }

    return "*Internal Job Alert!* A new position is now available exclusively for only internal team members. {$params['company']} is hiring for the role of {$params['title']}. For more details, please log in to your profile account at https://www.metier-quest.com/login";
}

private function buildInterviewJobMessage(array $params): ?string
{
    if (!isset($params['company'], $params['title'])) {
        return null;
    }

    return "*Interview Scheduled!* You have been selected for an interview with {$params['company']} for the {$params['title']} position. Please check your profile account for details on the date, time, and location at https://www.metier-quest.com/login";
}

// private function buildInterviewMessage(array $params): ?string
// {
//     if (!isset($params['company'], $params['title'])) {
//         return null;
//     }
    
//     return "*Interview Scheduled!* You've been selected for an interview with {$params['company']} for the {$params['title']} position. Check your profile for details at https://www.metier-quest.com/login";
// }
private function buildInterviewMessage(array $params): ?string
{
    $company = $params['company'] ?? $params['employer'] ?? '[Company]';
    $title = $params['title'] ?? $params['job_title'] ?? '[Position]';
    
    return "*Interview Scheduled!* You've been selected for an interview with {$company} for the {$title} position. Please check your profile account for details on the date, time, and location at https://www.metier-quest.com/login";
}
}
