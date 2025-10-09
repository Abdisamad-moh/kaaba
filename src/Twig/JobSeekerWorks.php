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
use Doctrine\Common\Collections\Collection;
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

#[AsLiveComponent(template: 'components/job_seeker_works_list.html.twig')]
class JobSeekerWorks
{
    use DefaultActionTrait;
    use ComponentToolsTrait;

    public ?Collection $works;

    // #[LiveProp]
    // public string $message;

    public function __construct(private Security $security,
        private EntityManagerInterface $em)
    {
        $this->works = $this->security->getUser()->getJobSeekerWorks();
    }

    #[LiveListener('worksSaved')]
    public function respondToEvent(#[LiveArg] ?string $resume_title)
    {
        $this->works = $this->security->getUser()->getJobSeekerWorks();
    }
}
