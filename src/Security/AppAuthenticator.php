<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $security;
    public function __construct(private UrlGeneratorInterface $urlGenerator,private EntityManagerInterface $entityManager)
    {

    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
      
        // dd($email);

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // dd("ss");
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $user = $token->getUser();
        
        $user->setLastActive(new \DateTimeImmutable());
        $this->entityManager->persist($user);
        $this->entityManager->flush(); 
        if (in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            
            return new RedirectResponse($this->urlGenerator->generate('app_std'));

        }elseif(in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            
            return new RedirectResponse($this->urlGenerator->generate('app_admin'));

        } elseif(in_array('ROLE_USER', $user->getRoles(), true)) {
            
            return new RedirectResponse($this->urlGenerator->generate('app_admin'));

        } 
        // dd($user);

        return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
