<?php

namespace App\Twig;

use Symfony\UX\LiveComponent\Attribute\LiveArg;
use App\Repository\InterviewQuestionsRepository;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use App\Repository\InterviewQuestionJobTitleRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('Qa')]
class Qa
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public ?string $query = null;

    #[LiveProp(writable: true)]
    public ?string $titleId = null;

    public function __construct(
        private InterviewQuestionJobTitleRepository $qjobtitles,
        private InterviewQuestionsRepository $qs
    ) {}

    public function getTitles(bool $data = false): array
    {
        if (empty($this->query)) {
            return  $this->qjobtitles->findBy(["is_special" => 1]);
        }
        if($data){
            return [];
        }

        return $this->qjobtitles->searchByQuery($this->query);
    }

    #[ExposeInTemplate]
    public function getQuestions(): array
    {
        if (!$this->titleId) {
            return [];
        }
        $this->getTitles(true);
        return $this->titleId ? $this->qs->findBy(["job_type" => $this->titleId]) : [];
       
    }
   
    #[LiveAction]
    public function selectTitle(#[LiveArg] int $id): void
    {
        $contact = $this->qjobtitles->find($id);
        
        if (!$contact) {
            return;
        }

        $this->titleId = $contact->getId();;
        $this->query = $contact->getName();

    }
   
}
