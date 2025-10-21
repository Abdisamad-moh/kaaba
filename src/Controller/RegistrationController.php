<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Service\MailService;
use Psr\Log\LoggerInterface;
use App\Event\SendEmailEvent;
use App\Security\EmailVerifier;
use App\Entity\JobseekerDetails;
use App\Entity\MetierEmailTemps;
use App\Form\OtpVerificationType;
use Symfony\Component\Mime\Email;
use App\Entity\MetierNotification;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\RecaptchaValidator;
use Symfony\Component\Mime\Address;
use App\Service\NotificationService;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Form\FormError;
use Symfony\Component\Mailer\Transport;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use App\Form\RegistrationEmployerFormType;
use App\Form\RegistrationJobseekerFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use App\Repository\MetierEmailTempsRepository;
use App\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaV3Type;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrueV3;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


#[Route('/signup')]
class RegistrationController extends AbstractController
{
    private $clientRegistry;
    private $security;
    private $requestStack;
    private $logger;
    private $eventDispatcher;

    public function __construct(
        ClientRegistry $clientRegistry, 
        Security $security, 
        RequestStack $requestStack, 
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        
        )
    {
        $this->clientRegistry = $clientRegistry;
        $this->security = $security;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

   

    private function getFormErrors(FormInterface $form)
    {
        $errors = [];

        // Global errors that do not belong to a specific field
        foreach ($form->getErrors() as $error) {
            $errors['global'][] = $error->getMessage();
        }

        // Field specific errors
        foreach ($form as $fieldName => $formField) {
            foreach ($formField->getErrors(true) as $error) {
                $errors[$fieldName][] = $error->getMessage();
            }
        }

        return $errors;
    }

    #[Route('/connect_google', name: 'connect_google')]
    public function connect_google(ClientRegistry $clientRegistry)
    {
        return $clientRegistry->getClient('google')->redirect(['openid', 'email', 'profile'], []);
    }

    

   

    // private function authenticateUser($user)
    // {
    //     // $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
    //     // $this->container->get('security.token_storage')->setToken($token);
    //     // $this->container->get('session')->set('_security_main', serialize($token));

    //     // $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
    //     // $this->security->getTokenStorage()->setToken($token);
    //     // $session = $this->requestStack->getSession();
    //     // $session->set('_security_main', serialize($token));
    //     $token = new UsernamePasswordToken($user, 'main', ['ROLE_JOBSEEKER']);
    //     $tokenStorage->setToken($token);
    //     $session->set('_security_main', serialize($token));
    //     return $this->redirectToRoute('app_home');

    //     // return $this->redirectToRoute('app_home');  // Or another route after login
    // }

   
 

    // #[Route('/mailtest', name: 'test_mail')]
    // public function sendTestMail(MailerInterface $mailer): Response
    // {
    //     $transport = Transport::fromDsn($_ENV['MAILER_DSN']);
    //     $mailer = new Mailer($transport);
    //     $email = (new Email())
    //         ->from('metier@systesa.net')
    //         ->to('omar.kollar@gmail.com')
    //         ->subject('Hello Email')
    //         ->text('Sending emails is fun!')
    //         ->html('<p>See Twig integration for better HTML integration!</p>');


    //     try {
    //         $mailer->send($email);
    //         // return new Response('Email sent!');
    //         dump('Email sent!');
    //         dd("hh");
    //     } catch (TransportExceptionInterface $e) {

    //         dump($this->logger->error('Email sending failed: ' . $e->getMessage()));
    //         dd("hh");
    //         return new Response('Email sending failed: ' . $e->getMessage());
    //     }
    // }

   
    
}
