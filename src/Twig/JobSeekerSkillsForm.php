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

use App\Entity\User;
use App\Entity\TodoList;
use App\Entity\JobSeekerWork;
use App\Entity\MetierCareers;
use App\Form\JobSeekerSkillsType;
use App\Form\TodoListFormType;
use App\Form\JobSeekerWorksType;
use Symfony\Component\Form\Form;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[AsLiveComponent(template: 'components/jobSeeker-skill.html.twig')]
class JobSeekerSkillsForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    use ComponentWithFormTrait;
    use DefaultActionTrait;
    use ComponentToolsTrait;

    #[LiveProp(fieldName: 'formData', writable: true)]
    public ?User $user;

    public function __construct(Security $security, private EntityManagerInterface $em)
    {
        $this->user = $security->getUser();
    }

    public function hasValidationErrors(): bool
    {
        return $this->getForm()->isSubmitted() && !$this->getForm()->isValid();
    }

    #[LiveAction]
    public function save(Request $request)
    {
        // $this->submitForm();

        // $data = json_decode($request->get('data'));
        // $job_seeker_works = $data->props->job_seeker_works->jobSeekerWorks;
        // foreach($this->user->getJobSeekerWorks() as $work) 
        // {
        //     $this->em->remove($work);
        //     $this->em->flush();
        // }

        $this->submitForm();

       

        foreach($this->getForm()->get('jobSeekerSkills')->getData() as $skill) 
        {
            $this->em->persist($skill);
        }
        
        $this->em->flush();

        $this->emit('worksSaved');
        $this->dispatchBrowserEvent('modal:close');

        // foreach($job_seeker_works as $work)
        // {
        //     $this->submitForm();

            
        //     // $new_work = new JobSeekerWork();
        //     // $new_work->setJobSeeker($this->user);
        //     // $new_work->setExperience($work->experience);
        //     // $new_work->setSalary($work->salary);
        //     // $new_work->setProfession($this->em->getRepository(MetierCareers::class)->find($work->profession));
        //     // $this->em->persist($new_work);
        //     // $this->em->flush();
        // }

    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            JobSeekerSkillsType::class,
            $this->user
        );
    }
}
