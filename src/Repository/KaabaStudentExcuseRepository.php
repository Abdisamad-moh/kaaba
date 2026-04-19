<?php
// src/Repository/KaabaStudentExcuseRepository.php

namespace App\Repository;

use App\Entity\KaabaStudentExcuse;
use App\Entity\KaabaApplication;
use App\Entity\KaabaInstitute;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class KaabaStudentExcuseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaStudentExcuse::class);
    }

    public function findActiveExcusesForDate(KaabaApplication $application, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.application = :application')
            ->andWhere('e.start_date <= :date')
            ->andWhere('e.end_date IS NULL OR e.end_date >= :date')
            ->andWhere('e.is_approved = true')
            ->setParameter('application', $application)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function findExcusesForDateRange(
        ?KaabaApplication $application = null,
        ?KaabaInstitute $institute = null,
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.application', 'app')
            ->leftJoin('e.institute', 'inst')
            ->orderBy('e.start_date', 'DESC');

        if ($application) {
            $qb->andWhere('e.application = :application')
                ->setParameter('application', $application);
        }

        if ($institute) {
            $qb->andWhere('e.institute = :institute')
                ->setParameter('institute', $institute);
        }

        if ($startDate) {
            $qb->andWhere('e.start_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('e.start_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calculate total excused days for a student in a date range
     * This sums up the days from each excuse (end_date - start_date + 1)
     */
    public function findExcusedDaysCountForPeriod(
        KaabaApplication $application,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): int {
        // Get all approved excuses for this period
        $excuses = $this->createQueryBuilder('e')
            ->where('e.application = :application')
            ->andWhere('e.is_approved = true')
            ->andWhere('e.start_date <= :endDate')
            ->andWhere('e.end_date IS NULL OR e.end_date >= :startDate')
            ->setParameter('application', $application)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
        
        // Calculate total days manually
        $totalDays = 0;
        foreach ($excuses as $excuse) {
            $excuseStart = $excuse->getStartDate();
            $excuseEnd = $excuse->getEndDate() ?? $excuse->getStartDate();
            
            // Calculate overlap with the period
            $overlapStart = max($excuseStart, $startDate);
            $overlapEnd = min($excuseEnd, $endDate);
            
            if ($overlapStart <= $overlapEnd) {
                $days = $overlapStart->diff($overlapEnd)->days + 1;
                $totalDays += $days;
            }
        }
        
        return $totalDays;
    }

    /**
     * Get total excused hours for a student in a date range
     */
    public function findExcusedHoursForPeriod(
        KaabaApplication $application,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): float {
        $excuses = $this->createQueryBuilder('e')
            ->where('e.application = :application')
            ->andWhere('e.is_approved = true')
            ->andWhere('e.start_date <= :endDate')
            ->andWhere('e.end_date IS NULL OR e.end_date >= :startDate')
            ->setParameter('application', $application)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
        
        $totalHours = 0;
        foreach ($excuses as $excuse) {
            $excuseStart = $excuse->getStartDate();
            $excuseEnd = $excuse->getEndDate() ?? $excuse->getStartDate();
            
            // Calculate overlap with the period
            $overlapStart = max($excuseStart, $startDate);
            $overlapEnd = min($excuseEnd, $endDate);
            
            if ($overlapStart <= $overlapEnd) {
                $days = $overlapStart->diff($overlapEnd)->days + 1;
                
                if ($excuse->getExcusedHoursPerDay()) {
                    $totalHours += $days * $excuse->getExcusedHoursPerDay();
                } else {
                    // If no specific hours, we need to get the expected hours per day from institute config
                    // This will be handled in the controller
                    $totalHours += $days * 8; // Default 8 hours if not specified
                }
            }
        }
        
        return $totalHours;
    }
}