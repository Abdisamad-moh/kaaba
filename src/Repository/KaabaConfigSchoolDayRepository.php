<?php

namespace App\Repository;

use App\Entity\KaabaConfigSchoolDay;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class KaabaConfigSchoolDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaConfigSchoolDay::class);
    }

    public function findAllSorted()
{
    return $this->createQueryBuilder('sd')
        ->orderBy('sd.orderIndex', 'ASC')
        ->addOrderBy("
            CASE sd.dayOfWeek
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
                ELSE 8
            END
        ", 'ASC')
        ->getQuery()
        ->getResult();
}

// Optional: Add this to your controller or a command to initialize days
private function initializeSchoolDays(EntityManagerInterface $em)
{
    $days = [
        ['dayOfWeek' => 'Monday', 'order' => 1, 'isSchoolDay' => true],
        ['dayOfWeek' => 'Tuesday', 'order' => 2, 'isSchoolDay' => true],
        ['dayOfWeek' => 'Wednesday', 'order' => 3, 'isSchoolDay' => true],
        ['dayOfWeek' => 'Thursday', 'order' => 4, 'isSchoolDay' => true],
        ['dayOfWeek' => 'Friday', 'order' => 5, 'isSchoolDay' => true],
        ['dayOfWeek' => 'Saturday', 'order' => 6, 'isSchoolDay' => false],
        ['dayOfWeek' => 'Sunday', 'order' => 7, 'isSchoolDay' => false],
    ];
    
    foreach ($days as $dayData) {
        $existing = $em->getRepository(KaabaConfigSchoolDay::class)
            ->findOneBy(['dayOfWeek' => $dayData['dayOfWeek']]);
            
        if (!$existing) {
            $schoolDay = new KaabaConfigSchoolDay();
            $schoolDay->setDayOfWeek($dayData['dayOfWeek']);
            $schoolDay->setOrderIndex($dayData['order']);
            $schoolDay->setIsSchoolDay($dayData['isSchoolDay']);
            $schoolDay->setIsHalfDay(false);
            $schoolDay->setDayType('normal');
            
            $em->persist($schoolDay);
        }
    }
    
    $em->flush();
}
}