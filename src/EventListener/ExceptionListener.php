<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionListener
{
    private UrlGeneratorInterface $urlGenerator;
    private string $environment;

    private const STATUS_CODE_ROUTES = [
        403 => 'app_home_access_denied',
        404 => 'app_home_access_denied',
        500 => 'app_home_access_denied',
        409 => 'app_home_access_denied',
        502 => 'app_home_access_denied',
        504 => 'app_home_access_denied',
        // Add more cases for other status codes as needed
    ];

    public function __construct(UrlGeneratorInterface $urlGenerator, string $environment)
    {
        $this->urlGenerator = $urlGenerator;
        $this->environment = $environment;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        if ($this->environment === 'prod') {
            $exception = $event->getThrowable();

            // Only handle HttpExceptionInterface exceptions
            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();

                // Check if the status code is in the list of defined routes
                if (array_key_exists($statusCode, self::STATUS_CODE_ROUTES)) {
                    $route = self::STATUS_CODE_ROUTES[$statusCode];
                    $response = new RedirectResponse($this->urlGenerator->generate($route));
                    $event->setResponse($response);
                }
            }
        }
    }
}


