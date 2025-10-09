<?php 
namespace App\Service;

use App\Entity\User;
use Twig\Environment;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

class MailService
{
    private Mailer $mailer;
    private Environment $twig;

    public function __construct(string $mailerDsn, Environment $twig)
    {
        $transport = Transport::fromDsn($mailerDsn);
        $this->mailer = new Mailer($transport);
        $this->twig = $twig;
    }

    public function generateOtp(): string
    {
        return random_int(100000, 999999); // Generate a 6-digit OTP
    }

    // public function sendOtp(User $user, string $otp, $htmlBody = null): void
    // {
    //     $email = (new Email())
    //         ->from('metier@systesa.net')
    //         ->to($user->getEmail())
    //         ->subject('Email Verification OTP')
    //         ->text("Your OTP for email verification is: $otp");

    //     if ($htmlBody) {
    //         $email->html($htmlBody);
    //     }

    //     $this->mailer->send($email);
    // }
    public function sendOtp(User $user, string $otp, string $templateName, array $context = []): void
    {
        // $template = $this->twig->load($templateName . '.html.twig');
        // $htmlBody = $template->render($context);

        // $email = (new Email())
        //     ->from('metier@systesa.net')
        //     ->to($user->getEmail())
        //     ->subject('Métier Quest - Account Verification')
        //     ->text("Your OTP for email verification is: $otp")
        //     ->html($htmlBody);

        // $this->mailer->send($email);
    }

    public function sendEmployerOtp(User $user, string $otp, string $templateName, array $context = []): void
    {
        
    }
    public function sendRegistrationMessage(User $user, string $templateName, array $context = []): void
    {
        // $template = $this->twig->load($templateName . '.html.twig');
        // $htmlBody = $template->render($context);

        // $from = 'Métier Quest <metier@systesa.net>';


        // if (in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            
        //     $subject = "Welcome to Métier Quest, Your Premier Job Board Platform";

        // }else {
            
        //     $subject = "Welcome to Métier Quest - Your Journey Begins Here!";
        // }

        // $email = (new Email())
        //     ->from($from)  // Use the variable containing both name and email
        //     ->to($user->getEmail())
        //     ->subject($subject)
        //     // ->text("Your OTP for email verification is: $otp")
        //     ->html($htmlBody);
        //     // ->setHeaders('Content-Type': 'text/html; charset=utf-8');
    
        // $this->mailer->send($email);
    }
    public function sendMail($subject, string $to_email, array $context = []): void
    {
        $template = $this->twig->load('email.html.twig');
        $htmlBody = $template->render($context);

        $from = $_ENV['MAIL_FROM'];


        $email = (new Email())
            ->from($from)  // Use the variable containing both name and email
            ->to($to_email)
            ->subject($subject)
            // ->text("Your OTP for email verification is: $otp")
            ->html($htmlBody);
            // ->setHeaders('Content-Type': 'text/html; charset=utf-8');
    
        $this->mailer->send($email);
    }
}