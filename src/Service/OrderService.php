<?php
// src/Service/NotificationService.php

namespace App\Service;

use DateTime;
use DateInterval;
use App\Entity\User;
use DateTimeImmutable;
use App\Entity\MetierOrder;
use App\Entity\MetierPlanUsed;
use App\Entity\MetierDownloadable;
use App\Entity\MetierNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderService
{
    private $entityManager;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function getActiveOrder(User $user): null
    {
        $em = $this->entityManager;
        $qb = $em->createQueryBuilder();

        

        return null;
    }

    public function getActiveSubscriptionOrder(User $user, string $type): null
    {
       return null;
    }
    

    public function updateBalances(): void
    {
       
    }

    
}
