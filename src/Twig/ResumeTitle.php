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
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

#[AsLiveComponent(template: 'components/resume_title.html.twig')]
class ResumeTitle
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: true)]
    public ?string  $resume_title;

    private JobseekerDetails $details;

    // #[LiveProp]
    // public string $message;

    public function __construct(Security $security, 
        private ValidatorInterface $validator,
        private ContainerBagInterface $params,
        private EntityManagerInterface $em)
    {
        $this->details = $security->getUser()->getJobSeekerDetails();
        $this->resume_title = $this->details->getResumeHeadline();
    }

    #[LiveAction]
    public function save_resume_title(Request $request)
    {
        // dd('dead');
        $this->details->setResumeHeadline($this->resume_title);
        $this->em->persist($this->details);
        $this->em->flush();
        
        $this->emit('resumeTitleSaved', ['resume_title' => $this->details->getResumeHeadline()]);
        $this->dispatchBrowserEvent('modal:close');
    }

    // public function getPackageCount(): int
    // {
    //     // return $this->packageRepository->count();
    // }
}
