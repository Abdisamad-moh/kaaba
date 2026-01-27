<?php

namespace App\Repository;

use App\Entity\KaabaAttendance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KaabaAttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaAttendance::class);
    }

    public function findAttendanceByApplicationAndDate($applicationId, $date)
    {
        return $this->createQueryBuilder('a')
            ->join('a.application', 'app')
            ->where('app.id = :applicationId')
            ->andWhere('a.attendance_date = :date')
            ->setParameter('applicationId', $applicationId)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAttendanceByInstituteAndDateRange($instituteId, $startDate, $endDate)
    {
        return $this->createQueryBuilder('att')
            ->join('att.application', 'app')
            ->join('app.institute', 'inst')
            ->where('inst.id = :instituteId')
            ->andWhere('att.attendance_date BETWEEN :startDate AND :endDate')
            ->setParameter('instituteId', $instituteId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('att.attendance_date', 'DESC')
            ->addOrderBy('att.check_in_time', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMonthlyAttendanceSummary($instituteId, $year, $month)
    {
        return $this->createQueryBuilder('att')
            ->select([
                'COUNT(att.id) as total_records',
                'SUM(CASE WHEN att.status = \'present\' THEN 1 ELSE 0 END) as present_count',
                'SUM(CASE WHEN att.status = \'late\' THEN 1 ELSE 0 END) as late_count',
                'SUM(CASE WHEN att.status = \'absent\' THEN 1 ELSE 0 END) as absent_count',
                'AVG(att.total_hours) as average_hours'
            ])
            ->join('att.application', 'app')
            ->join('app.institute', 'inst')
            ->where('inst.id = :instituteId')
            ->andWhere('YEAR(att.attendance_date) = :year')
            ->andWhere('MONTH(att.attendance_date) = :month')
            ->setParameter('instituteId', $instituteId)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleResult();
    }
}