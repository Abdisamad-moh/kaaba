<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TelesomSmsService
{
    private string $username;
    private string $password;
    private string $key;
    private string $sender;
    private string $endpoint;
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->username = $_ENV['SMS_USERNAME'];
        $this->password = $_ENV['SMS_PASSWORD'];
        $this->key = $_ENV['SMS_KEY'];
        $this->sender = $_ENV['SMS_SENDER'];
        $this->endpoint = $_ENV['SMS_ENDPOINT'];
        $this->client = $client;
    }

    /**
     * Normalize phone numbers to 9-digit local format (63xxxxxxxx)
     */
    public function normalizePhone(string $raw): array
    {
        // Replace separators
        $raw = str_replace(['/', ',', ';', '|'], ' ', $raw);

        $phones = preg_split('/\s+/', trim($raw));

        $cleaned = [];

        foreach ($phones as $phone) {
            // Remove +, spaces, hyphens
            $p = preg_replace('/[^0-9]/', '', $phone);

            // Remove leading 00 or 252
            $p = preg_replace('/^(00|252)/', '', $p);

            // Remove leading 0
            $p = preg_replace('/^0/', '', $p);

            // Now should be 9 digits
            if (strlen($p) === 9 && preg_match('/^6[1-9]/', $p)) {
                $cleaned[] = $p;
            }
        }

        return array_unique($cleaned);
    }

    /**
     * Send an SMS to a single phone number
     */
    public function send(string $to, string $message): array
    {
        // Encode spaces
        $msg = str_ireplace(" ", "%20", $message);

        $date = date('d/m/Y');

        $hashKey = strtoupper(md5(
            $this->username . "|" .
            $this->password . "|" .
            $to . "|" .
            $msg . "|" .
            $this->sender . "|" .
            $date . "|" .
            $this->key
        ));

        $params = [
            "from" => $this->sender,
            "to"   => $to,
            "msg"  => $msg,
            "key"  => $hashKey
        ];

        $response = $this->client->request('POST', $this->endpoint, [
            'body' => $params,
            'timeout' => 10
        ]);

        return [
            'phone' => $to,
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(false)
        ];
    }

    /**
     * Send to many numbers automatically
     */
    public function sendBulk(string $rawPhones, string $message): array
    {
        $phones = $this->normalizePhone($rawPhones);

        $results = [];

        foreach ($phones as $phone) {
            $results[] = $this->send($phone, $message);
        }

        return $results;
    }
}
