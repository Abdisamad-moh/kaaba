<?php
namespace App\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'app:update-job-status',
    description: 'Updates the status of EmployerJobs based on the application closing date.',
)]
class UpdateJobStatusCommand extends Command
{
    public function __construct(
      
        private EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTime();
        $jobs = [];
       
        $output->writeln(count($jobs));
        

        $output->writeln('Job statuses updated successfully.');
        //var/wwww/html/metier/bin/console app:update-job-status

        return Command::SUCCESS;
    }
}
