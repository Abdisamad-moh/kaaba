<?php
namespace App\Command;

use App\Model\JobStatusEnum;
use App\Event\SendEmailEvent;
use App\Repository\EmployerJobsRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\MetierEmailTempsRepository;
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
        private EmployerJobsRepository $jobsRepository,
        private JobApplicationRepository $applications,
        private MetierEmailTempsRepository $temps,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTime();
        $jobs = $this->jobsRepository->findJobsClosingToday($today);
        $test_job = $this->jobsRepository->find(32);
        $test_job->setJobDescription((new \DateTime())->format('Y-m-d H:i:s'));
        $this->jobsRepository->save($test_job, true);

        $output->writeln(count($jobs));
        $template = $this->temps->findOneBy(["action" => "jobseeker_application_closed"]);
        foreach ($jobs as $job) {
            $output->writeln($job->getTitle());

            //get all the applicants of this job
            $applicants =  $this->applications->findBy(['employer' => $job->getEmployer(), 'job' => $job->getId()]);
            foreach ($applicants as $applicant) {
                $output->writeln($applicant->getJobseeker()->getName());

                $d = [
                    "name" => $applicant->getJobseeker()->getName(),
                    "email" => $applicant->getJobseeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $job->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $job->getTitle(),
                    "link" => "",
                    "job_id" => "#" . $job->getId(),
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
            $job->setStatus('closed');
            $job->setExpired(true);
            $this->jobsRepository->save($job, true);
            $output->writeln('Updating jobs');
        }

        $output->writeln('Job statuses updated successfully.');
        //var/wwww/html/metier/bin/console app:update-job-status

        return Command::SUCCESS;
    }
}
