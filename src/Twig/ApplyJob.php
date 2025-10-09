<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\JobApplicationShortlist;
use App\Entity\JobReport;
use App\Entity\EmployerJobs;
use App\Event\SendEmailEvent;
use App\Entity\JobApplication;
use App\Entity\JobseekerDetails;
use App\Entity\MetierEmailTemps;
use App\Service\NotificationService;
use App\Service\OpenAIService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\JobApplicationRepository;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

#[AsLiveComponent]
class ApplyJob
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public bool $cover_letter_option = true;
    public bool $profile_cv = true;
    #[LiveProp(writable: true)]
    public bool $cv_option = true;
    #[LiveProp]
    public ?EmployerJobs $job;
    #[LiveProp]
    public ?JobReport $job_report;
    public ?string $flash_message = null;

    public ?JobApplication $job_application = null;

    public ?string $file_error = null;

    public ?string  $resume_file;

    private JobseekerDetails $details;

    #[LiveProp(writable: true)]
    public $cover_letter_file;
    #[LiveProp(writable: true)]
    public $cv_file;
    private $file_system;

    private $eventDispatcher;


    // #[LiveProp]
    // public string $message;

    public function __construct(private Security $security, 
        private ValidatorInterface $validator, 
        private ContainerBagInterface $params,
        private EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        private NotificationService $notificationService,
        private OpenAIService $openAIService,
        )
    {
        $this->file_system = new Filesystem();
        $this->resume_file = $this->security->getUser()->getJobSeekerDetails()->getCv();
        if($this->security->getUser()->getJobSeekerDetails()->getCv() != null) {
            $this->profile_cv = true;
            $this->cv_option = true;
        } else {
            $this->profile_cv = false;
            $this->cv_option = false;
        } 
        

        $this->details = $this->security->getUser()->getJobSeekerDetails();
        $this->eventDispatcher = $eventDispatcher;
    }

    public function mount(?array $answers)
    {
        // dd('yey');
        
    }

    #[PostMount]
    public function postMount(array $data)
    {
        // dd($data);
    }

    public function getJobApplication(JobApplicationRepository $applicationRepo): ?EmployerJobs
    {
        $this->application = $applicationRepo->findJobSeekerJob($this->security->getUser(), $this->job);
    }

    public function validateFile(?UploadedFile $file)
    {
        if(null === $file) {
            $this->file_error = 'No file was uploaded';
            return;
        }

        $errors = $this->validator->validate($file, [
            new Assert\File([
                'maxSize' => '2M',
                'extensions' => ['pdf']
            ])
        ]);

        if(0 === \count($errors)) return;

        $this->file_error = $errors->get(0)->getMessage();
        // dd($errors);
        // causes the component to re-render
        // throw new UnprocessableEntityHttpException('Validation failed');
    }

    public function getFileError()
    {
        return $this->file_error;
    }

    public function getResumeFile()
    {
        return $this->resume_file;
    }

    #[LiveAction]
    public function apply(Request $request, SluggerInterface $slugger)
    {
        
        // dd($request->files->get('cv_file'));
        // if($this->job->isRequireCoverLetter() == true)
        // {
        //     dd('dead requrired');
        //     $this->validateFile($request->files->get('cover_letter_file'));

        //     if($this->file_error != null)
        //     {
        //         return;
        //     }

        //     $file = $request->files->get('cover_letter_file');
            
        //     $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        //     $safeFilename = $slugger->slug($originalFilename);
        //     $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
        //     // dd('dead');
        //     try {
        //         $file->move(
        //             $this->params->get('logo_directory'),
        //             $newFilename
        //         );


        //         $job_application = new JobApplication();



        //         $job_application->setJob($this->job);
        //         $job_application->settype("self");
        //         $job_application->setStatus("applied");
        //         $job_application->setJobSeeker($this->security->getUser());
        //         $job_application->setCoverLetter($newFilename);
        //         $job_application->setEmployer($this->job->getEmployer());

        //         // create the notification for both jobseeker & employer
        //         $notification = $this->notificationService->createNotification("success", "You’ve successfully applied for the #" . $this->job->getId()  ." position at ". $this->job->getEmployer()->getName() ." Good luck!", $this->security->getUser(),"", []);
        //         $notification2 = $this->notificationService->createNotification("success", $this->security->getUser() ." has applied for the  #" . $this->job->getId()  ." position. Review the application now. ", $this->security->getUser(),"", []);
        //         $this->em->persist($notification);
        //         $this->em->persist($notification2);
        //         $this->em->persist($job_application);
        //         $this->em->flush();

        //         $temps = $this->em->getRepository(MetierEmailTemps::class);
        //         $template = $temps->findOneBy(["action" => "jobseeker_job_application"]) ?? new MetierEmailTemps();

               
        //         $d = [
        //             "name" => $this->security->getUser()->getName(),
        //             "email" => $this->security->getUser()->getEmail(),
        //             "type" => $template->getType(),
        //             "content" => $template->getContent(),
        //             "subject" => $template->getSubject(),
        //             "header" => $template->getHeader(),
        //             "cat" => "",
        //             "extra" => "",
        //             "otp" => "",
        //             "employer" => $this->job->getEmployer()->getName(),
        //             "interview_date" => "",
        //             "platform" => "",
        //             "job_title" => $this->job->getTitle(),
        //             "link" => "",
        //             "job_id" => "",
        //             "closing_date" => "",
        //             "interview_time" => "",
        //         ];
        //         $event = new SendEmailEvent($d);
        //         $this->eventDispatcher->dispatch($event, SendEmailEvent::class);


        //         $this->job_application = $job_application;
        //         if($this->job->getEmployerJobQuestions()) $this->dispatchBrowserEvent('application:submitted');

        //     } catch (FileException $e) {
        //         // Handle the exception 
        //         throw $e;
        //     }
        // } 
        // else {


            // dd($file_name_without_extension, $file_extension);
            // $new_name = $file_name  . date('YmdHis');

            try {

                // if coverletter is required
                if($this->job->isRequireCoverLetter() == true):
                    $this->validateFile($request->files->get('cover_letter_file'));

                    if($this->file_error != null)
                    {
                        return;
                    }

                    $file = $request->files->get('cover_letter_file');
                    
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                    $file->move(
                        $this->params->get('logo_directory'),
                        $newFilename
                    );
                    // end of cover letter requirement
                endif;

            
                $file_name = $this->details->getCv();
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_name_without_extension = pathinfo($file_name, PATHINFO_FILENAME);
                $new_file_name = $file_name_without_extension . date('YmdHis') . '.' . $file_extension;
                $directory = $this->params->get('logo_directory');
                
                $this->file_system->copy($directory . DIRECTORY_SEPARATOR . $file_name, $directory . DIRECTORY_SEPARATOR . $new_file_name);
               

                $job_application = new JobApplication();
                
                $job_application->setJob($this->job);
                $job_application->setStatus("applied");
                $job_application->settype("self");
                $job_application->setJobSeeker($this->security->getUser());
                $job_application->setCv($new_file_name);
                $job_application->setCoverLetter(isset($newFilename) ? $newFilename : null);
                $job_application->setEmployer($this->job->getEmployer());
                // create the notification for both jobseeker & employer
                $notification = $this->notificationService->createNotification("success", "You’ve successfully applied for the #" . $this->job->getId()  ." position at ". $this->job->getEmployer()->getName() ." Good luck!", $this->security->getUser(),"", []);
                $notification2 = $this->notificationService->createNotification("success", $this->security->getUser()->getName() ." has applied for the  #" . $this->job->getId()  ." position. Review the application now. ", $this->job->getEmployer(),"", []);
                $this->em->persist($notification);
                $this->em->persist($notification2);
                $this->em->persist($job_application);
                $this->em->flush();

                $user = $this->security->getUser();

                if($user->getJobSeekerResume()->getExperience() >= $this->job->getExperience() 
                    && $user->getJobSeekerResume()->getEducation() >= $this->job->getEducation()) 
                {
                    $educations = $user->getJobSeekerEducation()->map(function($edu) {
                        return $edu->getCourse();
                    })->toArray();

                    $educations = implode(", ", $educations);
                    $job_education_titles = $this->job->getEducationTitles();

                    $job_skills = $this->job->getRequiredSkill()->map(function($skill) {
                        return $skill->getName();
                    })->toArray();
                    $job_skills = implode(", ", $job_skills);

                    $job_seeker_skills = $user->getJobSeekerResume()->getSkills()->map(function($skill) {
                        return $skill->getName();
                    })->toArray();
                    $job_seeker_skills = implode(", ", $job_seeker_skills);
                    
                    $prompt = "
                        Given the following job education and skills requirement and cv's provided skills and education by candidate, provide a relevance score from 1 to 100 with no explanation. return a number response like 45 (numerical value).

                        Job Education:
                        $job_education_titles
                        Job Skills:
                        $job_skills

                        Cv Education:
                        $educations

                        Cv Skills:
                        $job_seeker_skills
                        
                        the focus should on the skills, and if they don't much with at least 5 skills, don't favor them.
                        Response format:
                        80
                    "; 

                    $response = $this->openAIService->compareJobAndCV('gpt-4o-mini', $prompt);
                    $response = $response['data'];

                    if($response >= 65) {
                        $shortlist = new JobApplicationShortlist();
                        $shortlist->setApplication($job_application);
                        $shortlist->setScore($response);
                        $this->em->persist($shortlist);
                        $this->em->flush();
                    }
                }

                $temps = $this->em->getRepository(MetierEmailTemps::class);
                $template = $temps->findOneBy(["action" => "jobseeker_job_application"]) ?? new MetierEmailTemps();

                $d = [
                    "name" => $this->security->getUser()->getName(),
                    "email" => $this->security->getUser()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" =>  $job_application->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $this->job->getTitle(),
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",

                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

                $this->job_application = $job_application;

                if($this->job->getEmployerJobQuestions()) $this->dispatchBrowserEvent('application:submitted');

            } catch (IOException $e) {
                throw $e;
            }
            // get from the system, the file with that name, and make a copy with timestamp added to its name and and store it in the same directory
            

            
        // }
        
    }

    public function getJob()
    {
        return $this->job;
    }


    // public function getPackageCount(): int
    // {
    //     // return $this->packageRepository->count();
    // }
}
