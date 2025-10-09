<?php 
namespace App\Service;

use DateTime;
use DateInterval;
use App\Entity\MetierOrder;
use App\Entity\MetierDownloadable;
use App\Entity\MetierNotification;
use App\Entity\MetierPackages;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;




class SubscriptionService
{
    private $entityManager;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

 

    public function createSixMonthsSubscription(User $user): ?MetierOrder
    {
        
        $package = $this->getPlan();

        if ($package) {
            // create order information
        $order = new MetierOrder();
        $order->setCustomer($user);
        $order->setAmount(0);
        $order->setPaymentStatus("paid");
        $order->setPlan($package);
        if ($package->getCategory() === "subscription") {
            $order->setValidityPeriod($package->getDuration());
        }
        $order->setOrderDate(new DateTime("now"));
        $order->setCustomerType($package->getCategory());
        $order->setCategory("employer");
        $order->setType("service");
        $order->setTax(0);
        $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

        
        return $order;
        }else{
            return null;
        }

    }

    public function getPlan(): ?MetierPackages
    {
        $package = $this->entityManager->getRepository(MetierPackages::class)->findOneBy(['status' => 1, 'type' => "employer", "category" => "subscription", "duration" => 6]);
        return $package;
    }

  
}

?>