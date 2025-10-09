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

    #[Route('/jobseeker', name: 'app_jobseeker_register')]
    public function jobseeker(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
        TokenStorageInterface $tokenStorage,
        MailService $mailService,
        RecaptchaValidator $recaptchaValidator,
        NotificationService $notificationService
    ): Response {

        // dd($session->get('redirect_after_auth'));
        $user = new User();
        $user->settype("jobseeker");
        $user->setVerified(false);
        $user->setOtpEnabled(false);
        $user->setOtpExpiration((new \DateTime())->modify('+10 minutes'));
        $user->setStatus(true);
        $user->setRoles(["ROLE_JOBSEEKER"]);
        $form = $this->createForm(RegistrationJobseekerFormType::class, $user);
        // $form->add('recaptcha', EWZRecaptchaV3Type::class, array(
        //     'action_name' => 'contact',
        //     'constraints' => array(
        //         new IsTrueV3()
        //     )
        // ));
        $errors = [];
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $remoteIp = $request->getClientIp();
            $verificationResult = $recaptchaValidator->verify($recaptchaResponse, $remoteIp);
            
            if (!$verificationResult) {
                // Handle the error, reCAPTCHA validation failed
                return $this->redirectToRoute('app_jobseeker_register', ['error' => 'reCAPTCHA validation failed']);
            } 

            $birthdate = $form->get('dob')->getData();

            $new_jobseeker_details = new JobseekerDetails();
            $new_jobseeker_details->setDob($birthdate);
            $new_jobseeker_details->setJobseeker($user);
            $new_jobseeker_details->setFirstName($form->get('firstName')->getData());
            $new_jobseeker_details->setMiddleName($form->get('middleName')->getData());
            $new_jobseeker_details->setLastName($form->get('lastName')->getData());

            $user->setUsername($form->get('email')->getData());
            $user->setName(ucfirst($new_jobseeker_details->getFirstName()) . ' ' . ucfirst($new_jobseeker_details->getMiddleName()) . ' ' . ucfirst($new_jobseeker_details->getLastName()));
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $registration_otp = $mailService->generateOtp();


            $temps = $entityManager->getRepository(MetierEmailTemps::class);
            $jobseeker_registration_message = $temps->findOneBy(["action" => "reg_msg_jobseeker"]) ?? new MetierEmailTemps();
            //send welcome message
                $jobseeker_registration_message_data = [
                    "name" => $user->getName(),
                    "email" => $user->getEmail(),
                    "type" => $jobseeker_registration_message->getType(),
                    "content" => $jobseeker_registration_message->getContent(),
                    "subject" => $jobseeker_registration_message->getSubject(),
                    "header" => $jobseeker_registration_message->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => "",
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($jobseeker_registration_message_data);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            // send the otp
            $jobseeker_otp_template = $temps->findOneBy(["action" => "jobseeker_reg_otp"]) ?? new MetierEmailTemps();
            $o = random_int(100000, 999999);
            $user->setOtp($o);
            $user->setVerified(false);
            $notification = $notificationService->createNotification(type:"success", message: "Welcome to Metier Quest, Your Account has been created", user: $user, routeName: "", routeParams: []);

            $entityManager->persist($new_jobseeker_details);
            $entityManager->persist($user);
            $entityManager->persist($notification);
            $entityManager->flush();
            $otp_email_data = [
                "name" => $user->getName(),
                "email" => $user->getEmail(),
                "type" => $jobseeker_otp_template->getType(),
                "content" => $jobseeker_otp_template->getContent(),
                "subject" => $jobseeker_otp_template->getSubject(),
                "header" => $jobseeker_otp_template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => $o,
                "employer" => "",
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];

            // dd($o);
            $event = new SendEmailEvent($otp_email_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            $this->addFlash('success', 'New Jobseeker has been registred successfully');
            //auto user login and then redirect to the verify_email
            // $userAuthenticator->authenticateUser(
            //     $user,
            //     $authenticator,
            //     $request
            // );
            $session = $request->getSession();
            $session->set('otp_verification_email', $user->getEmail());
            return $this->redirectToRoute('app_verify_email');
           
        }

        // $recaptchaResponse = $request->request->get('g-recaptcha-response');
        // dd($recaptchaResponse);

        return $this->render('registration/register_jobseeker.html.twig', [
            'form' => $form,
            'site_key' => $this->getParameter('recaptcha.site_key'),
            'errors' => $errors,
            'error' => $request->get('error')
        ]);
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

    #[Route('/login_redirect', name: 'login_redirect')]
    public function login_redirect(
        Request $request,
        LoggerInterface $logger,
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
        UserProviderInterface $userProvider

    ) {

        $client = $clientRegistry->getClient('google');
        $logger->info($request);
        try {

            $accessToken = $client->getAccessToken();
            $googleUser = $client->fetchUserFromToken($accessToken);

            // Log successful retrieval
            $logger->info('Google OAuth successful', [
                'Google User ID' => $googleUser->getId(),
                'To array' => $googleUser->toArray()
            ]);

            $logger->error('Google OAuth error', [
                'Google User ID' => $googleUser->getId(),
                'To array' => $googleUser->toArray()
            ]);

            $user = $this->findOrCreateUser($em, $passwordHasher, $googleUser->getEmail(), $googleUser);
            // return $this->authenticateUser($user);
            if(!$user) 
            {
                $request->getSession()->set('error_message', 'Please register first');
                $session->set('otp_verified', true);
                return $this->redirectToRoute('app_login');
            } 

            return $userAuthenticator->authenticateUser($user, $authenticator, $request);
            
        } catch (\Exception $e) {
            // Log any errors during the OAuth process

            $logger->error('Google OAuth error', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function findOrCreateUser(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        $email,
        $googleUser
    ) {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) return null;

        $user->setGoogleId($googleUser->getId());
        // $user_details = $user->getJobseekerDetails();
        if (!$user->getProfile()) $user->setProfile($googleUser->toArray()['picture']);

        $user->setType("jobseeker");
        $user->setVerified(true);

        $em->persist($user);
        // $em->persist($user_details);
        $em->flush();

        return $user;
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

    #[Route('/employer', name: 'app_signup_employer')]
    public function employer_register(
        Request $request,
        MailService $mailService,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
        MetierEmailTempsRepository $temps,
        RecaptchaValidator $recaptchaValidator,
        NotificationService $notificationService,
        SubscriptionService $subscriptionService
    ): Response {
        $user = new User();
        $user->settype("employer");
        $user->setVerified(true);
        $user->setStatus(true);
        $user->setRoles(["ROLE_EMPLOYER"]);
        $form = $this->createForm(RegistrationEmployerFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $remoteIp = $request->getClientIp();
            // TODO : Handle if recapcha is null, which comes when there is no internet mostly
            $verificationResult = $recaptchaValidator->verify($recaptchaResponse, $remoteIp);
            
            if (!$verificationResult) {
                // Handle the error, reCAPTCHA validation failed
                return $this->redirectToRoute('app_signup_employer', ['error' => 'reCAPTCHA validation failed']);
            } 

            $user->setUsername($form->get('email')->getData());
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $otp = $mailService->generateOtp();
            $notification = $notificationService->createNotification("success", "Welcome to Metier Quest, Your Account has been created", $user,"", []);
            $user->setOtp($otp);
            $user->setOtpExpiration((new \DateTime())->modify('+10 minutes'));
            $user->setVerified(false);

            // $new_subscription =  $subscriptionService->createSixMonthsSubscription($user);

            // if(!$new_subscription){
            //     $this->addFlash('danger', 'There has been an Error');
            //     return $this->redirectToRoute('app_signup_employer');
            // }
            $entityManager->persist($user);
            // $entityManager->persist($new_subscription);
            $entityManager->persist($notification);
            $entityManager->flush();

            // dd($user->getOtp($otp));
            $temps = $entityManager->getRepository(MetierEmailTemps::class);
            $employer_registration_message = $temps->findOneBy(["action" => "reg_msg_employer"]) ?? new MetierEmailTemps();
            //send welcome message
            $registration_message_data = [
                "name" => "",
                "email" => $user->getEmail(),
                "type" => $employer_registration_message->getType(),
                "content" => $employer_registration_message->getContent(),
                "subject" => $employer_registration_message->getSubject(),
                "header" => $employer_registration_message->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => $user->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($registration_message_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            // send the otp
            $employer_otp_template = $temps->findOneBy(["action" => "employer_reg_otp"]) ?? new MetierEmailTemps();
            $otp_email_data = [
                "name" => "",
                "email" => $user->getEmail(),
                "type" => $employer_otp_template->getType(),
                "content" => $employer_otp_template->getContent(),
                "subject" => $employer_otp_template->getSubject(),
                "header" => $employer_otp_template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => $otp,
                "employer" => $user->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];


            // 
            $event = new SendEmailEvent($otp_email_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            // dd($user->getOtp());
            $this->addFlash('success', 'New employer has been registred successfully');
            //redirect to a different page

            // Authenticate the user

            // Prepare credentials for authentication (assuming email as username)

            // Create an authentication token
            // Create an authentication token
            // $userAuthenticator->authenticateUser(
            //     $user,
            //     $authenticator,
            //     $request
            // );+


            //  u samee 6 months subscription


            $session = $request->getSession();
            $session->set('otp_verification_email', $user->getEmail());

            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('registration/register_employer.html.twig', [
            'form' => $form,
            'site_key' => $this->getParameter('recaptcha.site_key'),
            'error' => $request->get('error')
        ]);
    }
    #[Route('/resendOtp', name: 'app_signup_resendOtp')]
    public function resendOtp(Request $request, UserRepository $users, MailService $mailService, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {

        $email = $request->getSession()->get('otp_verification_email');
        if(!$email) return $this->redirectToRoute('app_login');

        $user = $users->findOneBy(['email' => $email]);

        $otp = $mailService->generateOtp();
        $user->setOtp($otp); // Assuming you have a setOtp method in User entity
        $user->setOtpExpiration((new \DateTime())->modify('+10 minutes'));
        $user->setVerified(false);

        $new_notification = new MetierNotification();
        $entityManager->persist($user);
        $entityManager->flush();
        // send the otp
        $mailService->sendOtp($user, $otp, 'email_verification', ['otp' => $otp, 'email' => $user->getEmail()]);
        $this->addFlash('success', 'Authentication code to verify your email is sent to your email');
        //redirect to a different page
        
        $employer_otp_template = $entityManager->getRepository(MetierEmailTemps::class)->findOneBy(["action" => "employer_reg_otp"]) ?? new MetierEmailTemps();
        $otp_email_data = [
            "name" => "",
            "email" => $user->getEmail(),
            "type" => $employer_otp_template->getType(),
            "content" => $employer_otp_template->getContent(),
            "subject" => $employer_otp_template->getSubject(),
            "header" => $employer_otp_template->getHeader(),
            "cat" => "",
            "extra" => "",
            "otp" => $otp,
            "employer" => $user->getName(),
            "interview_date" => "",
            "platform" => "",
            "job_title" => "",
            "link" => "",
            "job_id" => "",
            "closing_date" => "",
            "interview_time" => "",
        ];


        $event = new SendEmailEvent($otp_email_data);
        $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
        
        return $this->redirectToRoute('app_verify_email');
        // do anything else you need here, like send an email
    }

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

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $users,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
    ): Response {

        $session = $request->getSession();
        $email = $session->get('otp_verification_email'); // Retrieve email from session
        
        if (!$email) {
            // If the email is not in the session, redirect to login or an error page
            return $this->redirectToRoute('app_login');
        }

        // dd($email);

        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        // $session = $request->getSession();
        $form = $this->createForm(OtpVerificationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $otpCode = implode('', $formData);
            // $em->clear();
            $currentUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            // Check if the OTP has expired
            if ($currentUser->getOtpExpiration() < new \DateTime()) {
                $this->addFlash('error', 'Your OTP has expired. Please request a new one.');
                return $this->redirectToRoute('app_verify_email');
            }

            // Check if OTP matches
            if ($currentUser->getOtp() == $otpCode) {
                // OTP is valid
                $currentUser->setOtp(null); // Clear OTP after successful verification
                $currentUser->setOtpExpiration(null); // Clear expiration
                $currentUser->setVerified(true); // Mark user as verified
                $currentUser->setOtpAttempts(0); // Reset attempts
                $em->persist($currentUser);
                $em->flush();
                $session->set('otp_verified', true);
                // Authenticate the user
                $userAuthenticator->authenticateUser($currentUser, $authenticator, $request);

                $session->remove('otp_verification_email');
                // Redirect to dashboard or home
                if (in_array('ROLE_EMPLOYER', $currentUser->getRoles(), true)) {
                    
                    // imika u samee default 6 months subscription

                    if($currentUser->getEmployerDetails()) {
                        return $this->redirectToRoute('app_employer_settings'); 
                    }
                    return $this->redirectToRoute('app_employer');
        
                }elseif(in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles(), true)) {
                    
                    return $this->redirectToRoute('app_admin');
        
                }elseif(in_array('ROLE_JOBSEEKER', $currentUser->getRoles(), true)) {
                    
                    $session = $request->getSession();
    if ($session->has('redirect_after_auth')) {
        $redirectUrl = $session->get('redirect_after_auth');
        $session->remove('redirect_after_auth'); // clean up
        return $this->redirect($redirectUrl);
    }
                    return $this->redirectToRoute('app_job_seeker_profile');
        
                }
            
            } else {
                // Invalid OTP
                $currentUser->setOtpAttempts($currentUser->getOtpAttempts() + 1); // Increment OTP attempts
                if ($currentUser->getOtpAttempts() >= 3) {
                    $this->addFlash('error', 'Too many failed attempts. Please request a new OTP.');
                    return $this->redirectToRoute('app_verify_email');
                }
                
                $this->addFlash('error', 'Invalid OTP. Please try again.');
                return $this->redirectToRoute('app_verify_email');
            }
            // dd($otpCode, $email);
            // if ($currentUser->getOtp() == $otpCode) {
            //     $currentUser->setVerified(true);
            //     $currentUser->setOtp(null); // Clear the OTP

            //     $em->persist($currentUser);
            //     $em->flush();

            //     $this->addFlash('success', 'Email verified successfully!');
            //     if (in_array('ROLE_EMPLOYER', $currentUser->getRoles(), true)) {

            //         return $this->redirectToRoute('app_employer');
            //     } elseif (in_array('ROLE_JOBSEEKER', $currentUser->getRoles(), true)) {

            //         return $this->redirectToRoute('app_job_seeker_profile');
            //     }
            // } else {
            //     $this->addFlash('error', 'Invalid OTP');
            // }
        }




        return $this->render('verify.html.twig', [
            'otpForm' => $form->createView(),
            'email' => $email
        ]);
    }
    
}
