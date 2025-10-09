<?php
// src/Service/OpenAIService.php

namespace App\Service;

use GuzzleHttp\Client;

class OpenAIService
{
    private $client;
    private $apiKey;

    public function __construct(string $openAIKey)
    {
        $this->client = new Client(['base_uri' => 'https://api.openai.com/v1/']);
        $this->apiKey = $openAIKey;
    }

    public function getEmbeddings(string $text)
    {
        $response = $this->client->post('embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                // 'model' => 'text-embedding-3-large', // Choose your preferred model
                // 'model' => 'text-embedding-3-small', // Choose your preferred model
                'model' => 'text-embedding-ada-002', // Choose your preferred model
                'input' => $text,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['data'][0]['embedding'] ?? null;
    }

    public function compareJobAndCV(string $model = 'gpt-4o-mini', string $prompt)
    {
        
        $response = $this->client->post('chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model, // or 'gpt-4'
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                // 'max_tokens' => 2000,
            ],
        ]);

        $response = json_decode($response->getBody()->getContents(), true);
        $data = $response['choices'][0]['message']['content'] ?? null;
        return $data ? ['response' => $response, 'data' => $data] : null;
    }
}
