<?php

namespace App\Twig;

use App\Form\JobQuestionType;
use App\Entity\EmployerJobQuestion;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent()]
class JobQuestionForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;



    #[LiveProp(fieldName: 'formData',writable: true)]
    public ?EmployerJobQuestion $question;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            JobQuestionType::class,
            $this->question
        );
    }
}

?>
