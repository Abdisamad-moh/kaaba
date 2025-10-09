<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailService;
use App\Event\SendEmailEvent;
use App\Entity\MetierEmailTemps;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    function __construct(private EntityManagerInterface $em){

    }
    #[Route(path: '/login', name: 'app_login', methods:["GET","POST"])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, Security $security, UrlGeneratorInterface $urlGenerator): Response
    {
  $session = $request->getSession();
        // dd($session->get('redirect_after_auth'));
        // Check if the user is logged in
        $user = $security->getUser();

        $title = "";
        $message = "";

        $type = $request->query->get('type');
        // dd($type);
        if($type == "employer"){
            $title = "Find the Right Talent & Grow Your Business";
             $message = "Sign In to connect with top talent and manage your hiring process seamlessly";
        }else{
            $title = "Find a job & Grow your career";
            $message = "Sign Up to access the most advanced job search engine";
        }

        if ($user) {
            // Redirect based on user roles
            if (in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
                if (!$this->em->getRepository(User::class)->find($user)->getEmployerDetails()) {
                    return new RedirectResponse($urlGenerator->generate('app_employer_settings'));
                }
                return new RedirectResponse($urlGenerator->generate('app_employer'));
            } elseif (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
                return new RedirectResponse($urlGenerator->generate('app_admin'));
            } elseif (in_array('ROLE_JOBSEEKER', $user->getRoles(), true)) {
                 $session = $request->getSession();

        // 1️⃣ Highest priority: redirect_after_auth session (from checkout, register, etc.)
        $redirectUrl = $session->get('redirect_after_auth');
                 if ($redirectUrl) {
                $session->remove('redirect_after_auth');
                return new RedirectResponse($redirectUrl);
            }
            // return new RedirectResponse($urlGenerator->generate('app_jobseeker_purchase_checkout', [
            //     "package" => 374,
            // ]));
                return new RedirectResponse($urlGenerator->generate('app_job_seeker_profile'));
            }
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Handle custom error messages
        $errorMessage = '';
        if ($request->getSession()->get('error_message')) {
            $errorMessage = $request->getSession()->get('error_message');
            $request->getSession()->remove('error_message');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'errorMessage' => $errorMessage,
            'site_key' => $this->getParameter('recaptcha.site_key')
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(Request $request, UserRepository $users, MailService $mailService, EntityManagerInterface $em, RouterInterface $router, EventDispatcherInterface $eventDispatcher): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $users->findOneBy(['email' => $form->get('email')->getData()]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $hashedToken = password_hash($token, PASSWORD_DEFAULT);
                $user->setResetToken($hashedToken);
                $user->setResetTokenExpiration(new \DateTime('+1 hour'));
                $em->persist($user);
                $em->flush();

                $resetUrl = $router->generate('app_reset_password', [
                    'token' => $token
                ], UrlGeneratorInterface::ABSOLUTE_URL);

                $employer_otp_template = $em->getRepository(MetierEmailTemps::class)->findOneBy(["action" => "user_password_forgot"]) ?? new MetierEmailTemps();
                $otp_email_data = [
                    "name" => "",
                    "email" => $user->getEmail(),
                    "type" => $employer_otp_template->getType(),
                    "content" => $employer_otp_template->getContent(),
                    "subject" => $employer_otp_template->getSubject(),
                    "header" => $employer_otp_template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $user->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => $resetUrl,
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];


                // 
                $event = new SendEmailEvent($otp_email_data);
                $eventDispatcher->dispatch($event, SendEmailEvent::class);
            }

            sweetalert()->success("Done. Please check your email with a link to reset your password");
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('security/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/employer_login', name: 'app_employer_login')]
    public function employer_login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }
        dd("yes");

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/employer-login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: 'reset-password/{token}', name: 'app_reset_password')]
    public function app_reset_password(Request $request, UserRepository $userRepo, EntityManagerInterface $em, string $token, UserPasswordHasherInterface $passwordHasher)
    {
        // Find the user by the hashed reset token
        $user = $userRepo->findOneByResetToken($token);

        $verify_token = password_verify($token, $user?->getResetToken() ?? '');
        $expiration_time = $user?->getResetTokenExpiration() ?? null;

        if (!$user || !$verify_token || $expiration_time < new \DateTime()) {
            // Token is invalid or expired
            throw $this->createNotFoundException('Page not found!');
        }

        $form = $this->createFormBuilder()
            ->add('newPassword', PasswordType::class, [
                'label' => 'New Password',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter your new password',
                    'class' => 'form-control'
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm New Password',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Confirm your new password',
                    'class' => 'form-control'
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $newPassword = $form->get('newPassword')->getData();
                $confirmPassword = $form->get('confirmPassword')->getData();

                if ($newPassword !== $confirmPassword) {
                    // Add a custom error message for password mismatch
                    $form->get('confirmPassword')->addError(new FormError('Passwords do not match.'));
                }
            });

        $form = $form->getForm();


        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the new password
            $newPassword = $form->get('newPassword')->getData();
            $encodedPassword = $passwordHasher->hashPassword($user, $newPassword);

            // Set the new password and clear the reset token
            $user->setPassword($encodedPassword);
            $user->setResetToken(null);
            $user->setResetTokenExpiration(null);
            $em->flush();

            sweetalert()->success("Your password is reset successfully. Please login with your new password.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset-password.html.twig', [
            'resetPasswordForm' => $form->createView(),
        ]);
    }
}
