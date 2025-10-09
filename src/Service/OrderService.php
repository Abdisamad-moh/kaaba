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

    public function getActiveOrder(User $user): ?MetierOrder
    {
        $em = $this->entityManager;
        $qb = $em->createQueryBuilder();

        $qb->select('o')
            ->from(MetierOrder::class, 'o')
            ->where('o.customer = :user')
            ->andWhere('o.valid_from <= :now')
            ->andWhere('o.valid_to >= :now')
            ->andWhere('o.canceled != 1 OR o.canceled IS NULL')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable());
        $qb->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function getActiveSubscriptionOrder(User $user, string $type): ?MetierOrder
    {
        $order = $this->getActiveOrder($user);
        if (!$order) {
            return null;
        }
    
        $planUsedRepository = $this->entityManager->getRepository(MetierPlanUsed::class);
        $usedCount = $planUsedRepository->createQueryBuilder('p')
            ->select('COALESCE(COUNT(p.id), 0)')
            ->where('p.subscription = :subscription') // Check the specific subscription (order)
            ->andWhere('p.type = :type')
            ->setParameter('subscription', $order)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    
        $availableBalance = match ($type) {
            'job' => $order->getPlan()->getJobBalance(),
            'course' => $order->getPlan()->getCourseBalance(),
            'tender' => $order->getPlan()->getTenderBalance(),
            default => null,
        };
    
        if ($availableBalance === null || (int)$usedCount >= (int)$availableBalance) {
            return null;
        }
    
        return $order;
    }
    

    public function updateBalances(MetierOrder $order, string $type, int $amount = 1): void
    {
        $plan = $order->getPlan();
        if (!$plan) {
            return;
        }

        for ($i = 0; $i < $amount; $i++) {
            $planUsed = new MetierPlanUsed();
            $planUsed->setPlan($plan);
            $planUsed->setType($type);
            $planUsed->setDate(new DateTime());
            $planUsed->setBalance(1);

            $this->entityManager->persist($planUsed);
        }

        $this->entityManager->flush();
    }

    public function createDownloadables(string $type,  User $user, MetierOrder $order, int $number_of_months)
    {
        $all_categories = ["resume", "resume2", "cover"];
        $free_categories = ["resume2", "cover"];
        if ($type === "free") {
            // waa laba uun free ha u helo sida resume and cover no template by profession


            for ($i = 1; $i <= $number_of_months; $i++) {

                foreach ($free_categories as $key => $category) {
                    # code...

                    $notification = new MetierDownloadable();
                    $notification->setType($category);
                    $notification->setHasDownloaded(false);
                    $notification->setPurchase($order);
                    $notification->setPurchase($order);
                    $notification->setUser($user);
                    $now = new DateTime("now");
                    $nextMonth = $now->add(new DateInterval("P{$number_of_months}M"));
                    $notification->setExpirationDate($nextMonth);

                    $this->entityManager->persist($notification);
                }
            }
        } else {
            for ($i = 1; $i <= $number_of_months; $i++) {

                foreach ($all_categories as $key => $category) {
                    # code...

                    $notification = new MetierDownloadable();
                    $notification->setType($category);
                    $notification->setHasDownloaded(false);
                    $notification->setPurchase($order);
                    $notification->setPurchase($order);
                    $notification->setUser($user);
                    $now = new DateTime("now");
                    $nextMonth = $now->add(new DateInterval("P{$number_of_months}M"));
                    $notification->setExpirationDate($nextMonth);

                    $this->entityManager->persist($notification);
                }
            }
        }


        // $this->entityManager->persist($notification);
        // $this->entityManager->flush();

    }

    public function deleteNotification(MetierNotification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }
}
