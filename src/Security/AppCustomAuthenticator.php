<?php

namespace App\Security;

use App\Entity\User;
use App\Service\MailService;
use App\Event\SendEmailEvent;
use App\Entity\MetierEmailTemps;
use App\Repository\UserRepository;
use App\Service\RecaptchaValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    public const EMPLOYER_LOGIN_ROUTE = 'app_employer_login';

    private $mailService;
    private $em;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher, 
        private UserRepository $userRepo, 
        private UrlGeneratorInterface $urlGenerator, 
        private RecaptchaValidator $recaptchaValidator, 
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorageInterface,
        MailService $mailService, 
        EntityManagerInterface $em
    ) {
        $this->mailService = $mailService;
        $this->em = $em;
    }

    public function authenticate(Request $request): Passport
    {
        $route = $request->attributes->get('_route');

        // Determine form field names dynamically based on route
        $emailField = $route === self::EMPLOYER_LOGIN_ROUTE ? 'employer_email' : 'email';
        $passwordField = $route === self::EMPLOYER_LOGIN_ROUTE ? 'employer_password' : 'password';

        // reCAPTCHA Validation
        // $recaptchaResponse = $request->request->get('g-recaptcha-response');
        // if (null === $recaptchaResponse) {
        //     throw new AuthenticationException('reCAPTCHA validation failed.');
        // }

        $remoteIp = $request->getClientIp();
        // $verificationResult = $this->recaptchaValidator->verify($recaptchaResponse, $remoteIp);

        // if (!$verificationResult) {
        //     throw new AuthenticationException('reCAPTCHA validation failed.');
        // }

        // Extract credentials
        $email = $request->request->get($emailField);
        $password = $request->request->get($passwordField);

        // Find user by email
        $user = $this->userRepo->findOneBy(['email' => $email]);
        if (!$user) {
            throw new AuthenticationException('Invalid credentials.');
        }

        // Store last email in session
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user = $token->getUser();
        $session = $request->getSession();

        // Check OTP
        if ($user->isOtpEnabled() && !$session->get('otp_verified')) {
            $this->tokenStorageInterface->setToken(null);
            $request->getSession()->invalidate();

            // Generate OTP
            $otp = random_int(100000, 999999);
            $user->setOtp($otp);
            $user->setOtpExpiration(new \DateTime('+10 minutes'));
            $this->em->persist($user);
            $this->em->flush();

            // Send OTP email
            $employer_otp_template = $this->em->getRepository(MetierEmailTemps::class)->findOneBy(["action" => "jobseeker_login_otp"]) ?? new MetierEmailTemps();
            $otp_email_data = [
                "name" => $user->getName(),
                "email" => $user->getEmail(),
                "type" => $employer_otp_template->getType(),
                "content" => $employer_otp_template->getContent(),
                "subject" => $employer_otp_template->getSubject(),
                "header" => $employer_otp_template->getHeader(),
                "otp" => $otp,
            ];

            $event = new SendEmailEvent($otp_email_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            $session->set('otp_verification_email', $user->getEmail());
            return new RedirectResponse($this->router->generate('app_verify_email'));
        }

        // Update last active timestamp
        $user->setLastActive(new \DateTimeImmutable());
        $this->em->persist($user);
        $this->em->flush();

        // Role-based redirection
        if (in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            if (!$this->em->getRepository(User::class)->find($user)->getEmployerDetails()) {
                return new RedirectResponse($this->urlGenerator->generate('app_employer_settings'));
            }
            return new RedirectResponse($this->urlGenerator->generate('app_employer'));
        } elseif (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin'));
        } elseif (in_array('ROLE_JOBSEEKER', $user->getRoles(), true)) {
             $redirectUrl = $session->get('redirect_after_auth');
        
                 if ($redirectUrl) {
                   
                $session->remove('redirect_after_auth');
                return new RedirectResponse($redirectUrl);
            }
            return new RedirectResponse($this->urlGenerator->generate('app_job_seeker_profile'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        $route = $request->attributes->get('_route');

        return match ($route) {
            self::EMPLOYER_LOGIN_ROUTE => $this->urlGenerator->generate(self::EMPLOYER_LOGIN_ROUTE),
            default => $this->urlGenerator->generate(self::LOGIN_ROUTE),
        };
    }
}
