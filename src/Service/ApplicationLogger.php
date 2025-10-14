<?php
// src/Service/ApplicationLogger.php

namespace App\Service;

use App\Entity\KaabaApplication;
use App\Entity\KaabaApplicationLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationLogger
{
    public function __construct(private EntityManagerInterface $em) {}

    public function log(
        KaabaApplication $application,
        string $action,
        ?string $note = null,
        ?User $user = null
    ): void {
        $log = new KaabaApplicationLog();
        $log->setApplication($application);
        $log->setAction($action);
        $log->setUser($user);

        // Enhance note for specific actions
        switch ($action) {
            case 'status_change':
                $currentStatus = $application->getStatus() ? $application->getStatus()->getName() : 'Unknown';
                $note = sprintf(
                    "Status changed to: %s. %s",
                    $currentStatus,
                    $note ?? ''
                );
                break;
            case 'revert':
                $note = sprintf(
                    "Status reverted. %s",
                    $note ?? ''
                );
                break;
            case 'created':
                $note = "Application submitted by applicant";
                break;
        }

        $log->setNote($note);
        $application->addLog($log);

        $this->em->persist($log);
        $this->em->flush();
    }
}