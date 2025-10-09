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
use App\Entity\MetierChat;
use App\Entity\EmployerJobs;
use App\Entity\JobApplication;
use App\Entity\JobseekerDetails;
use App\Repository\MetierChatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\LiveResponder;
use App\Repository\JobApplicationRepository;
use App\Repository\UserRepository;
use DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Validator\Validation;

#[AsLiveComponent]
class Chat
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ComponentToolsTrait;

    private $contacts;

    #[ExposeInTemplate('view')]
    #[LiveProp(writable: true)]
    public ?string $view = null;

    #[LiveProp(writable: true)]
    #[NotBlank]
    public string $message = '';


    #[LiveProp(writable: true)]
    #[NotBlank]
    public ?MetierChat $chat = null;

    #[LiveProp(writable: true, url: true)]
    public ?int $query = null;




    #[LiveProp(writable: true)]
    public ?User $selectedContact = null;






    // #[LiveProp(writable: true)]
    // public $cv_file;
    // private $file_system;

    // #[LiveProp]
    // public string $message;

    private User $user;

    public function __construct(
        private EntityManagerInterface $em,
        private MetierChatRepository $metierChatRepository,
        private UserRepository $userRepository,
        private Security $security,

    ) {
        $this->user = $this->security->getUser();
    }



    #[LiveAction]
    public function apply(Request $request, SluggerInterface $slugger)
    {
        // dd($request->files->get('cv_file'));


    }

    #[ExposeInTemplate]
    public function getContacts(): array
    {
        return $this->metierChatRepository->findContactsByUser($this->user);
    }

    #[LiveAction]
    public function saveChat(EntityManagerInterface $entityManager, LiveResponder $liveResponder): array
    {
        // $this->validate();
        $validator = Validation::createValidator();

        $constraints = new Assert\Length([
            'min' => 1,
            'max' => 300,
            'minMessage' => 'The string must be at least 1 characters long',
            'maxMessage' => 'The string cannot be longer than 300 characters',
        ]);

        // Validate the string
        $string = $this->message;
        $violations = $validator->validate($string, $constraints);

        // Check for violations
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                dump($violation->getMessage() . "\n");
            }
        } 

        // dd($this->message);

        $chat = new MetierChat();
        $chat->setContent($this->message);
        $chat->setSender($this->user);
        $chat->setSeen(false);
        $chat->setReceiver($this->selectedContact);
        $chat->setDate(new DateTime("now"));
        $entityManager->persist($chat);
        $entityManager->flush();

        // $this->dispatchBrowserEvent('modal:close');
        // $this->emit('chat:created', [
        //     'chat' => $chat->getId(),
        // ]);

        // reset the fields in case the modal is opened again
        $this->message = '';
       return  $this->getChatMessages();
    }

    #[LiveListener('chat:created')]
    public function onChatCreated(#[LiveArg] MetierChat $chat): void
    {
        // change chat to the new one
        $this->chat = $chat;
    }

    #[LiveAction]
    public function selectContact(#[LiveArg] int $id): void
    {
        $contact = $this->userRepository->find($id);

        // dump($this->view);
        // dd($id);

        if (!$contact) {
            // Handle user not found
            // dd("no id found");
            return;
        }

        $chats = $this->metierChatRepository->
            findBy(['sender' => $contact, 'receiver' => $this->user]);
        
        foreach($chats as $chat)
        {
            $chat->setSeen(true);
            $this->em->persist($chat);
            $this->em->flush();

        }
        
        $this->selectedContact = $contact;
    }


    #[ExposeInTemplate]
    public function getChatMessages(): array
    {
        if (!$this->selectedContact) {
            return [];
        }

        return $this->metierChatRepository->findChatsBetweenUsers(
            $this->user,
            $this->selectedContact
        );
    }
}
