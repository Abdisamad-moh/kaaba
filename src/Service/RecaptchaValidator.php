<?php
// src/Service/RecaptchaValidator.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaValidator
{
    private $client;
    private $secretKey;

    public function __construct(HttpClientInterface $client, string $secretKey)
    {
        $this->client = $client;
        $this->secretKey = $secretKey;
    }

    public function verify(string $recaptchaResponse, string $remoteIp): bool
    {
        $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $recaptchaResponse,
                'remoteip' => $remoteIp
            ]
        ]);
        $data = $response->toArray();

        return $data['success'] && isset($data['score']) && $data['score'] >= 0.5;
    }
}