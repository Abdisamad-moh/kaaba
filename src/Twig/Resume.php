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

use App\Entity\JobseekerDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

#[AsLiveComponent(template: 'components/job_seeker_profile_resume.html.twig')]
class Resume
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public string $type = 'success';
    public ?string $resume_header;
    public ?string $flash_message = null;

    public ?string $file_error = null;

    private ?string  $resume_file;

    private JobseekerDetails $details;

    #[LiveProp(writable: true)]
    public $cv_file;

    // #[LiveProp]
    // public string $message;

    public function __construct(Security $security, 
        private ValidatorInterface $validator, 
        private ContainerBagInterface $params,
        private EntityManagerInterface $em)
    {
        $user = $security->getUser();
        if ($user && $user->getJobseekerDetails()) {
            $this->resume_header = $user->getJobseekerDetails()->getResumeHeadline();
            $this->resume_file = $user->getJobseekerDetails()->getCv();
            $this->details = $user->getJobseekerDetails();
        } else {
            $this->resume_header = null;
            $this->resume_file = null;
            $this->details = null;
        }
    }

    public function mount()
    {
        // dd('yey');
        $this->resume_header = $this->details->getResumeHeadline();
        // dd($this->resume_header);
        
    }

    #[PostMount]
    public function postMount(array $data)
    {
        // dd($data);
    }

    public function getResumeHeader()
    {
        return $this->resume_header;
    }
    
    #[LiveAction]
    public function upload_cv(Request $request, SluggerInterface $slugger)
    {
        // dd('dead');
        $this->validateFile($request->files->get('cv_file'));

        if($this->file_error == null)
        {
            $file = $request->files->get('cv_file');
            
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            // dd('dead');
            try {
                $file->move(
                    $this->params->get('logo_directory'),
                    $newFilename
                );

                $this->details->setCv($newFilename);
                $this->em->persist($this->details);
                $this->em->flush();

                $this->resume_file = $this->details->getCv();
                // $this->session->getF
                $this->dispatchBrowserEvent('resume:updated');

            } catch (FileException $e) {
                // Handle the exception
                throw $e;
            }
        }
        
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

    #[LiveListener('resumeTitleSaved')]
    public function respondToEvent(#[LiveArg] ?string $resume_title)
    {
        $this->resume_header = $resume_title;
    }

    // public function getPackageCount(): int
    // {
    //     // return $this->packageRepository->count();
    // }
}
