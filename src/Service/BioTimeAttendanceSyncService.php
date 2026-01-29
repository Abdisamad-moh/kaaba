<?php

namespace App\Service;

use App\Entity\KaabaAttendance;
use App\Repository\KaabaAttendanceRepository;
use App\Repository\KaabaStudentDeviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BioTimeAttendanceSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly KaabaStudentDeviceRepository $studentDeviceRepository,
        private readonly KaabaAttendanceRepository $attendanceRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array $transactions Raw BioTime transactions array
     */
    public function sync(array $transactions): void
    {
        foreach ($transactions as $transaction) {
            try {
                $this->processTransaction($transaction);
            } catch (\Throwable $e) {
                $this->logger->error('BioTime attendance sync failed', [
                    'transaction' => $transaction,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->em->flush();
    }

    private function processTransaction(array $transaction): void
    {
        /**
         * Expected BioTime transaction structure (example):
         * {
         *   "id": "987654",
         *   "emp": 23,
         *   "terminal": 2,
         *   "punch_time": "2026-01-26 08:12:34"
         * }
         */

        if (!isset($transaction['emp'], $transaction['id'], $transaction['punch_time'])) {
            return;
        }

        $employeeId = (string) $transaction['emp'];

        // 1. Find student device by biotime_employee_id
        $studentDevice = $this->studentDeviceRepository->findOneBy([
            'biotime_employee_id' => $employeeId,
        ]);

        if (!$studentDevice) {
            // Student not enrolled in BioTime
            return;
        }

        // 2. Check if transaction already saved
        $existingAttendance = $this->attendanceRepository->findOneBy([
            'biotime_transaction_id' => (string) $transaction['id'],
        ]);

        if ($existingAttendance) {
            // Already synced
            return;
        }

        // 3. Create new attendance
        $attendance = new KaabaAttendance();
        $attendance->setApplication($studentDevice->getApplication());
        $attendance->setDevice($studentDevice->getDevice());
        $attendance->setAttendanceType('biometric');
        $attendance->setInstitute($studentDevice->getApplication()->getInstitute());
        $attendance->setBiotimeTransactionId((string) $transaction['id']);

        $checkInTime = new \DateTime($transaction['punch_time']);

        $attendance->setCheckInTime($checkInTime);
        $attendance->setAttendanceDate(
            (clone $checkInTime)->setTime(0, 0, 0)
        );

        $attendance->setStatus('present');

        $this->em->persist($attendance);

        // 4. Update last attendance date on student device
        $studentDevice->setLastAttendanceDate($checkInTime);
    }
}
