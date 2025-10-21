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

 

    

  
}

?>