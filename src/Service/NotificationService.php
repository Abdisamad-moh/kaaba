<?php 
// src/Service/NotificationService.php

namespace App\Service;

use App\Entity\MetierNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
    private $entityManager;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function createNotification(string $type, string $message, $user, string $routeName, array $routeParams = []): void
    {
      

       
    }

    public function deleteNotification(): void
    {
        
    }
}

?>