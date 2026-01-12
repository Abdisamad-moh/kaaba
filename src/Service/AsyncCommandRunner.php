<?php 
// src/Service/AsyncCommandRunner.php
// src/Service/AsyncCommandRunner.php

namespace App\Service;

use Symfony\Component\Process\Process;
use Psr\Log\LoggerInterface;

class AsyncCommandRunner
{
    private string $projectDir;
    private LoggerInterface $logger;
    private string $phpBinary;

    public function __construct(string $projectDir, LoggerInterface $logger)
    {
        $this->projectDir = $projectDir;
        $this->logger = $logger;
        $this->phpBinary = PHP_BINARY; // full path to PHP
    }

    public function runCommandAsync(string $command, array $arguments = []): Process
{
    $cmd = [
        $this->phpBinary,
        $this->projectDir . '/bin/console',
        $command,
        '--env=prod',
        '--no-interaction'
    ];

    foreach ($arguments as $k => $v) {
        $cmd[] = $k;
        $cmd[] = (string)$v;
    }

    $env = [
        'APP_ENV' => 'prod',
        'APP_DEBUG' => '0',
        'DATABASE_URL' => $_ENV['DATABASE_URL'],
        'APP_SECRET' => $_ENV['APP_SECRET'],
    ];

    $process = new Process($cmd, $this->projectDir, $env);
    $process->start();

    return $process;
}

}
