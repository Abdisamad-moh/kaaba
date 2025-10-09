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

    public function createNotification(string $type, string $message, $user, string $routeName, array $routeParams = []): MetierNotification
    {
        $notification = new MetierNotification();
        $notification->setType($type);
        $notification->setContent($message);
        $notification->setUser($user);
        $notification->setRead(false);
        // $notification->setAction($this->urlGenerator->generate($routeName, $routeParams));
        $notification->setAction("");

        // $this->entityManager->persist($notification);
        // $this->entityManager->flush();

        return $notification;
    }

    public function deleteNotification(MetierNotification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }
}

?>