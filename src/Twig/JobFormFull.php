<?php

namespace App\Twig;

use App\Form\JobFormType;
use App\Entity\EmployerJobs;
use App\Form\JobQuestionType;
use App\Form\JobFormTypeShort;
use App\Entity\EmployerJobQuestion;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent()]
class JobFormFull extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;



    #[LiveProp(fieldName: 'formData',writable: true)]
    public ?EmployerJobs $job;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            JobFormType::class,
            $this->job
        );
    }
}

?>
