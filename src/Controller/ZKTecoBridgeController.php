<?php

namespace App\Controller;

use App\Entity\KaabaApplication;
use App\Entity\KaabaAttendance;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ZKTecoBridgeController extends AbstractController
{
    #[Route('/api/bridge/attendance', name: 'receive_attendance', methods: ['POST'])]
    public function receiveAttendance(Request $request, LoggerInterface $logger, EntityManagerInterface $em): JsonResponse
    {
        $records = json_decode($request->getContent(), true) ?? [];

        if (empty($records)) {
            return $this->json(['ok' => true, 'received' => 0]);
        }

        $logger->info('ZKTeco bridge attendance received', [
            'record_count' => count($records),
            'facility'     => $records[0]['facility'] ?? 'unknown',
        ]);

        $saved   = 0;
        $skipped = 0;
        $errors  = [];

        foreach ($records as $record) {
            $pin       = $record['pin']       ?? null;
            $timestamp = $record['timestamp'] ?? null;

            if (!$pin || !$timestamp) {
                $skipped++;
                continue;
            }

            // PIN is the KaabaApplication ID
            $applicationId = (int) $pin;

            $application = $em->getRepository(KaabaApplication::class)->find($applicationId);

            if (!$application) {
                $logger->warning('ZKTeco attendance: application not found', [
                    'pin'            => $pin,
                    'application_id' => $applicationId,
                ]);
                $skipped++;
                continue;
            }

            try {
                $punchTime     = new \DateTime($timestamp);
                $attendanceDate = (clone $punchTime)->setTime(0, 0, 0);
            } catch (\Exception $e) {
                $errors[] = "Invalid timestamp: {$timestamp}";
                $skipped++;
                continue;
            }

            // Check if a record already exists for this application on this date
            $existing = $em->getRepository(KaabaAttendance::class)->findOneBy([
                'application'     => $application,
                'attendance_date' => $attendanceDate,
            ]);

            if ($existing) {
                // Not the first punch of the day -- update check_out_time
                // Only update if this punch is later than the current check_out (or check_in)
                $currentLatest = $existing->getCheckOutTime() ?? $existing->getCheckInTime();

                if ($punchTime > $currentLatest) {
                    $existing->setCheckOutTime($punchTime);
                    $existing->setUpdatedAt(new \DateTime());
                    $existing->calculateTotalHours();
                    $saved++;
                } else {
                    $skipped++;
                }
            } else {
                // First punch of the day -- create new attendance record as check-in
                $attendance = new KaabaAttendance();
                $attendance->setApplication($application);
                $attendance->setAttendanceDate($attendanceDate);
                $attendance->setCheckInTime($punchTime);
                $attendance->setAttendanceType('biometric');
                $attendance->setInstitute($application->getInstitute());
                $attendance->setStatus('present');
                $attendance->setCreatedAt(new \DateTime());

                $em->persist($attendance);
                $saved++;
            }
        }

        $em->flush();

        $logger->info('ZKTeco attendance processed', [
            'saved'   => $saved,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);

        return $this->json([
            'ok'      => true,
            'saved'   => $saved,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);
    }

    #[Route('/api/bridge/commands/pending', name: 'commands_pending', methods: ['GET'])]
    public function commandsPending(Request $request, LoggerInterface $logger): JsonResponse
    {
        $logger->info('ZKTeco bridge polling commands', [
            'facility' => $request->query->get('facility'),
        ]);

        // return $this->json([
        //     'commands' => [
        //         [
        //             'id'     => 'test_cmd_002',
        //             'action' => 'get_user_template',
        //             'device' => 'gate1',     // must match exactly the device name in the bridge
        //             'pin'    => '001',
        //         ]
        //     ]
        // ]);

        // return $this->json([
        //     'commands' => [
        //         [
        //             'id'     => 'test_cmd_push_001',
        //             'action' => 'push_user',
        //             'device' => 'gate1',
        //             'pin'    => '1',
        //             'user'   => [
        //                 'pin'       => '1',
        //                 'name'      => 'Test Employee Caraale',
        //                 'card'      => 0,
        //                 'privilege' => 0,
        //                 'password'  => '',
        //             ],
        //             'template' => 'SqdTUzIxAAAD5OcECAUHCc7QAAAb5WkBAAAAgwkqleQxAHkPWgDxAPPrqAA3AAYOfQA65IUOuABHAMgOsORMAH8OXAC8AO/rrwB9ABAPuwCB5PYPSQCTAJsPheSlAP8PhwB3AA3rkAC/AEIPIgDB5CwPgQDJAJ0P0eTTADkPiQAdAFfrNADcAF0OAQDc5EoPPQDrABILVOTqAFwNZQAxANTqygD0AEoPggD55OELWAD9AJANOuQBAWsLPwDKAWHvUQATAdMMlAAe5XENsAAdAQ4Od+QeAWIPOgDkAerpXQAmAd0OWAAs5UoOsgA/AYEN4eRAAboO0wCBATHqigBJAWEMUQBK5UEMXQBUAdwPq+RVASQO7gCfAS7rFHUS9Sr+SnvQ45LvgYQJjVj6PpyAgEkF0JLwiBbu3XbN/bF6K5ChEbbvN5DvGP4GwR0XDYfo/nTSaiyHwPCxvzoJ9BCaKLy65TSp7UjzuhO756vmVQTUGfaqqPEtD5LxHPz68+9KUYhOfaf1GvFoAhojVXwU+fKJPIRhCYV+1IRCmCP46QICEUqAEpCojEkJFYBwdOodBIxFCN2VFPgqdGh56Gc5cBz6z3sslK0SGYMziYxklIai9a6SoImSnoAbQWxNDlx+GmdQC4UPkSIc+7HgQIj1j0t4jHjVFGsFICIuPD8YwwZUHg33KzyauGA+8OYx4u7ExHfxGebrnqKOsWAAI8oAAcgVRwvFjxeejVjCZgUASh+DJ40FAJcf/ThXAORIKWnBCgBUMHklbm3BCQDF9YnDJv+dwQwApPN9wYLDZFsFAFbyZ8OXBAC8OgYzygC3oYJ6esH/wATBbeEBvEUGMwXFvEjrwT4NAK5ORneAJF3AEwDpaFVlxBmBwMNkYsDLAFyT6j1AMDwIxbJ44vz/Nv0EAG98gGMIALKBEP46PUHrAfeLnsF5tIP8dQcARZNkbzoVAxSSoICAgv8EwX0l/sH+BgBJUlpRJQkAiqL6IDsvE+SKsAb7+/04Rvyg/koTAOW9bHjBJsDDwsDEwATCwht2CQCMwU87wMOB/goAk8I97f9EGQUAf81ccNQA0iqxhMLG/8YHwcEbwMBWBwDPEz3DJvopBhDDIpLFxMkFANbWNDvCAIQ5Uf7AUwUAAuNDH4UVADzn05FLNxr+/DPA/VfcAEQR21r/wf79Pvz/GP79/sD//wX//ST+/xcAWvkVOPwk+ff7/f3ABP3DJMH//cDBx8MAyB9NwP2BAwCA/V8bBQBT/1zBOgMTqyBi/gQQceFePOcR30cwwATV5zrQhAMQtUItOgMTx0Q3wFJCAM5DAuQBC0VS',
        //             'fid'    => 6,
        //         ]
        //     ]
        // ]);

        return $this->json(['commands' => []]);
    }

    #[Route('/api/bridge/commands/{id}/ack', name: 'command_ack', methods: ['POST'])]
    public function commandAck(string $id, Request $request, LoggerInterface $logger): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $logger->info('akn data', [$data]);
        // $logger->info('ZKTeco bridge command ack', [
        //     'command_id' => $id,
        //     'success'    => $data['success'] ?? null,
        //     'message'    => $data['message'] ?? null,
        // ]);

        return $this->json(['ok' => true]);
    }

    #[Route('/api/bridge/templates', name: 'receive_template', methods: ['POST'])]
    public function receiveTemplate(Request $request, LoggerInterface $logger): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $logger->info('ZKTeco bridge template received', [
            'facility'  => $data['facility'] ?? null,
            'pin'       => $data['pin']      ?? null,
            'fid'       => $data['fid']      ?? null,
            'has_data'  => !empty($data['template']),
            'template_data' => $data['template'],
            'size'      => strlen($data['template'] ?? ''),
        ]);

        return $this->json(['ok' => true]);
    }

    
}
