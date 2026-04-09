<?php

namespace App\Command;

use App\Service\BioTimeService;
use App\Service\BioTimeAttendanceSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

#[AsCommand(
    name: 'app:biotime:sync-attendance',
    description: 'Sync attendance transactions from BioTime',
)]
class BioTimeSyncAttendanceCommand extends Command
{
    public function __construct(
        private BioTimeService $bioTimeService,
        private BioTimeAttendanceSyncService $attendanceSyncService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'Start date (Y-m-d)')
            ->addOption('to', null, InputOption::VALUE_OPTIONAL, 'End date (Y-m-d)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            // ✅ Default to TODAY if empty
            $from = $input->getOption('from') ?? date('Y-m-d');
            $to   = $input->getOption('to') ?? date('Y-m-d');

            $output->writeln("<info>Fetching attendance from $from to $to...</info>");

            // 1. Fetch
            $transactions = $this->bioTimeService->getAttendanceTransactions($from, $to);

            // 2. Sync
            $this->attendanceSyncService->sync($transactions);

            $count = count($transactions);

            $output->writeln("<info>SUCCESS: $count transactions synced.</info>");

            $this->logger->info('BioTime attendance synced via command', [
                'count' => $count,
                'from' => $from,
                'to' => $to
            ]);

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $this->logger->error('Attendance sync failed', [
                'error' => $e->getMessage()
            ]);

            $output->writeln('<error>Sync failed: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }
    }
}
