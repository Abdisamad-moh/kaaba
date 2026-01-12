<?php 
// src/Service/SimpleBackgroundRunner.php

namespace App\Service;

use Psr\Log\LoggerInterface;

class SimpleBackgroundRunner
{
    private string $projectDir;
    private LoggerInterface $logger;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->projectDir = $projectDir;
        $this->logger = $logger;
    }

    public function runBulkSmsCommand(
        int $userId,
        array $statusIds,
        array $instituteIds,
        string $message,
        string $jobId
    ): bool {
        // Build the command
        $cmd = sprintf(
            'cd %s && %s bin/console app:send-bulk-sms %d --statuses=%s --institutes=%s --message=%s --job-id=%s --no-interaction > /dev/null 2>&1 &',
            escapeshellarg($this->projectDir),
            PHP_BINARY,
            $userId,
            escapeshellarg(json_encode($statusIds)),
            escapeshellarg(json_encode($instituteIds)),
            escapeshellarg($message),
            escapeshellarg($jobId)
        );
        
        $this->logger->info('Executing background command', ['cmd' => $cmd]);
        
        // Execute
        exec($cmd, $output, $returnVar);
        
        return $returnVar === 0;
    }
}