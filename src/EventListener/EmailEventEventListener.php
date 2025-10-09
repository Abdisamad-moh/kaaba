<?php

namespace App\EventListener;

use Twig\Environment;
use App\Service\MailService;
use App\Event\SendEmailEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailEventEventListener implements EventSubscriberInterface
{
    private $mailService;
    private $entityManager;
    private $twig;



    public function __construct(Environment $twig, MailService $mailService, EntityManagerInterface $entityManager)
    {
        $this->mailService = $mailService;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
    }

    public static function getSubscribedEvents()
    {
        return [
            SendEmailEvent::class => 'onSendEmailEvent',
        ];
    }

    public function onSendEmailEvent(SendEmailEvent $event)
    {
        // Get the data from the event
        $data = $event->getData();

        // Extract the array elements to variables
        extract($data);

        // Prepare the context array for Twig
        $context = [
            'name' => $name,
            'email' => $email,
            'type' => $type,
            'cat' => $cat,
            'subject' => $subject,
            'header' => $header,
            'content' => $content,
            "otp" => $otp,
            "employer" => $employer,
            "interview_date" => $interview_date,
            "platform" => $platform,
            "job_title" => $job_title,
            "link" => $link,
            "job_id" => $job_id,
            "closing_date" => $closing_date,
            "interview_time" => $interview_time,
            // Add more fields if needed
        ];

        // Optionally include "extra" if it exists
        if (array_key_exists('extra', $data)) {
            $context['extra'] = $data['extra'];
        }
        if (array_key_exists('probation_period', $data)) {
            $context['probation_period'] = $data['probation_period'];
        }
        if (array_key_exists('start_date', $data)) {
            $context['start_date'] = $data['start_date'];
        }
        if (array_key_exists('salary', $data)) {
            $context['salary'] = $data['salary'];
        }
        if (array_key_exists('note', $data)) {
            $context['note'] = $data['note'];
        }

        // Render the content with Twig
        $renderedContent = $this->twig->createTemplate($content)->render($context);



        // Update the context with the rendered content
        $context['content'] = $renderedContent;
        // dd($context['content']);
        // Send the email
        $this->mailService->sendMail($subject, $email, $context);
        // dd($email);
        // Example: Use the mail service to send an email
        // $this->mailService->sendEmail($data);

        // dump("Received data: " . $data);
    }
}
