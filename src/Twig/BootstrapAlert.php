<?php 

namespace App\Twig;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'components/bootstrap_alert.html.twig')]
class BootstrapAlert
{
    public ?string $message = null;
    public ?array $element_classes = [];
}