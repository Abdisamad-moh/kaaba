<?php

namespace App\Repository;

use App\Entity\KaabaGender;
use App\Entity\KaabaRegion;
use App\Entity\KaabaDistrict;
use App\Entity\KaabaApplication;
use App\Entity\KaabaScholarship;
use App\Entity\KaabaQualification;
use App\Entity\KaabaApplicationStatus;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<KaabaApplication>
 */
class KaabaApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaApplication::class);
    }

public function filterApplications(
        ?KaabaApplicationStatus $status = null,
        ?\DateTimeInterface $fromDate = null,
        ?\DateTimeInterface $toDate = null,
        ?string $phone = null,
        ?KaabaRegion $region = null,
        ?KaabaDistrict $district = null,
        ?KaabaQualification $qualification = null,
        ?KaabaGender $gender = null,
        ?KaabaScholarship $scholarship = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.status', 's')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.district', 'd')
            ->leftJoin('a.highest_qualification', 'q')
            ->leftJoin('a.gender', 'g')
            ->leftJoin('a.scholarship', 'sch')
            ->orderBy('a.created_at', 'DESC');

        if ($status) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($fromDate) {
            $qb->andWhere('a.application_date >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        if ($toDate) {
            $qb->andWhere('a.application_date <= :toDate')
                ->setParameter('toDate', $toDate);
        }

        if ($phone) {
            $qb->andWhere('a.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }

        if ($region) {
            $qb->andWhere('a.region = :region')
                ->setParameter('region', $region);
        }

        if ($district) {
            $qb->andWhere('a.district = :district')
                ->setParameter('district', $district);
        }

        if ($qualification) {
            $qb->andWhere('a.highest_qualification = :qualification')
                ->setParameter('qualification', $qualification);
        }

        if ($gender) {
            $qb->andWhere('a.gender = :gender')
                ->setParameter('gender', $gender);
        }

        if ($scholarship) {
            $qb->andWhere('a.scholarship = :scholarship')
                ->setParameter('scholarship', $scholarship);
        }

        return $qb->getQuery()->getResult();
    }


    // Count applications by status
    public function countApplicationsByStatus(string $status): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->join('a.status', 's')
            ->where('s.name = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Count total applications
    public function countTotalApplications(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Count applications from last year
    public function countLastYearApplications(): int
    {
        $lastYear = new \DateTime('first day of January last year');
        $endOfLastYear = new \DateTime('last day of December last year');

        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.application_date BETWEEN :start AND :end')
            ->setParameter('start', $lastYear)
            ->setParameter('end', $endOfLastYear)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Count applications by region
    public function countApplicationsByRegion(): array
    {
        return $this->createQueryBuilder('a')
            ->select('r.name as region_name, COUNT(a.id) as application_count')
            ->join('a.region', 'r')
            ->groupBy('r.id')
            ->orderBy('application_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Count applications by district
    public function countApplicationsByDistrict(): array
    {
        return $this->createQueryBuilder('a')
            ->select('d.name as district_name, COUNT(a.id) as application_count')
            ->join('a.district', 'd')
            ->groupBy('d.id')
            ->orderBy('application_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Count applications by gender
    public function countApplicationsByGender(): array
    {
        return $this->createQueryBuilder('a')
            ->select('g.name as gender_name, COUNT(a.id) as application_count')
            ->join('a.gender', 'g')
            ->groupBy('g.id')
            ->getQuery()
            ->getResult();
    }

    // Count applications by institute
    public function countApplicationsByInstitute(): array
    {
        return $this->createQueryBuilder('a')
            ->select('i.name as institute_name, COUNT(a.id) as application_count')
            ->join('a.institute', 'i')
            ->groupBy('i.id')
            ->orderBy('application_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Count applications by scholarship
    public function countApplicationsByScholarship(): array
    {
        return $this->createQueryBuilder('a')
            ->select('s.title as scholarship_title, COUNT(a.id) as application_count')
            ->join('a.scholarship', 's')
            ->groupBy('s.id')
            ->orderBy('application_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Count applications by month for current year
   // Count applications by month for current year
public function countApplicationsByMonth(): array
{
    $currentYear = (new \DateTime())->format('Y');
    $startDate = new \DateTime("$currentYear-01-01");
    $endDate = new \DateTime("$currentYear-12-31");

    $applications = $this->createQueryBuilder('a')
        ->select('a.application_date')
        ->where('a.application_date BETWEEN :start AND :end')
        ->setParameter('start', $startDate)
        ->setParameter('end', $endDate)
        ->getQuery()
        ->getResult();

    // Process in PHP
    $monthlyCounts = array_fill(1, 12, 0);
    
    foreach ($applications as $application) {
        $month = (int)$application['application_date']->format('n'); // 1-12
        $monthlyCounts[$month]++;
    }

    $result = [];
    foreach ($monthlyCounts as $month => $count) {
        $result[] = [
            'month' => $month,
            'application_count' => $count
        ];
    }

    return $result;
}

    // Get recent applications
    public function findRecentApplications(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 's', 'r', 'sch')
            ->leftJoin('a.status', 's')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.scholarship', 'sch')
            ->orderBy('a.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
