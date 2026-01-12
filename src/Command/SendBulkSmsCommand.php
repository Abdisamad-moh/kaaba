<?php
// src/Command/SendBulkSmsCommand.php

namespace App\Command;

use App\Entity\User;
use App\Entity\KaabaSmsLog;
use App\Service\TelesomSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\KaabaApplicationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:send-bulk-sms',
    description: 'Send bulk SMS messages in background'
)]
class SendBulkSmsCommand extends Command
{
    protected static $defaultName = 'app:send-bulk-sms';
    protected static $defaultDescription = 'Send bulk SMS messages in background';

    private $applicationRepository;
    private $smsService;
    private $em;
    private $security;

    public function __construct(
        KaabaApplicationRepository $applicationRepository,
        TelesomSmsService $smsService,
        EntityManagerInterface $em,
        Security $security
    ) {
        parent::__construct();
        $this->applicationRepository = $applicationRepository;
        $this->smsService = $smsService;
        $this->em = $em;
        $this->security = $security;
    }

    protected function configure()
    {
        $this
            ->addArgument('user-id', InputArgument::REQUIRED, 'User ID who initiated the job')
            ->addOption('statuses', null, InputOption::VALUE_REQUIRED, 'JSON array of status IDs')
            ->addOption('institutes', null, InputOption::VALUE_REQUIRED, 'JSON array of institute IDs')
            ->addOption('message', null, InputOption::VALUE_REQUIRED, 'Message template')
            ->addOption('job-id', null, InputOption::VALUE_OPTIONAL, 'Job tracking ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Bulk SMS Sender');

        // Get inputs
        $userId = $input->getArgument('user-id');
        $statusIds = json_decode($input->getOption('statuses') ?? '[]', true);
        $instituteIds = json_decode($input->getOption('institutes') ?? '[]', true);
        $messageTemplate = $input->getOption('message');
        $jobId = $input->getOption('job-id');

        // Get user
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            $io->error('User not found!');
            return Command::FAILURE;
        }

        // Apply security logic for ROLE_USER
        if (in_array('ROLE_USER', $user->getRoles(), true)) {
            $instituteIds = $user->getKaabaInstitutes()
                ->map(fn($i) => $i->getId())
                ->toArray();
        }

        // Get applications
        $applications = $this->applicationRepository->findByFilters($statusIds, $instituteIds);
        $totalCount = count($applications);

        $io->note("Found {$totalCount} recipients");
        $io->progressStart($totalCount);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($applications as $app) {
            try {
                $name = $app->getFullName() ?? "Applicant";
                $phone = $app->getPhone();

                if (!$phone) {
                    $io->progressAdvance();
                    continue;
                }

                $message = str_replace('{{name}}', $name, $messageTemplate);
                usleep(400000); // Throttle

                $sendResults = $this->smsService->sendBulk($phone, $message);

                $raw = $sendResults[0]['status'] ?? null;
                $decoded = is_string($raw) ? json_decode($raw, true) : null;

                $gatewayStatus = $decoded['status'] ?? 'unknown';
                $gatewayMsg = $decoded['msg'] ?? $raw;

                // Save log
                $log = new KaabaSmsLog();
                $log->setCreatedBy($user);
                $log->setApplication($app);
                $log->setReceiverName($name);
                $log->setPhoneNumber($phone);
                $log->setMessage($message);
                $log->setFilteredStatuses($statusIds);
                $log->setFilteredInstitutes($instituteIds);
                $log->setMessageStatus($gatewayStatus);
                $log->setGatewayResponse($gatewayMsg);

                $this->em->persist($log);

                if ($gatewayStatus === 'success') {
                    $sentCount++;
                } else {
                    $failedCount++;
                    $io->warning("Failed to send to {$phone}: {$gatewayMsg}");
                }


               

                // Flush periodically
                if ($sentCount % 50 === 0) {
                    $this->em->flush();
                }

            } catch (\Exception $e) {
                $failedCount++;
                $io->error("Error sending to {$phone}: {$e->getMessage()}");
            }

            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success([
            "Job completed!",
            "Total recipients: {$totalCount}",
            "Successfully sent: {$sentCount}",
            "Failed: {$failedCount}",
        ]);

        return Command::SUCCESS;
    }
}