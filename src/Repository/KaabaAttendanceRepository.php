<?php

namespace App\Repository;

use App\Entity\KaabaAttendance;
use App\Entity\KaabaApplication;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

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

    // In src/Repository/KaabaAttendanceRepository.php
public function findByApplicationAndDateRange(
    KaabaApplication $application,
    \DateTimeInterface $startDate,
    \DateTimeInterface $endDate
): array {
    return $this->createQueryBuilder('a')
        ->where('a.application = :application')
        ->andWhere('a.attendance_date BETWEEN :startDate AND :endDate')
        ->setParameter('application', $application)
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->orderBy('a.attendance_date', 'ASC')
        ->addOrderBy('a.check_in_time', 'ASC')
        ->getQuery()
        ->getResult();
}
// In src/Repository/KaabaAttendanceRepository.php
public function getDailyAttendanceSummary(\DateTimeInterface $date): array
{
    return $this->createQueryBuilder('a')
        ->select([
            'a.application',
            'MIN(a.check_in_time) as first_check_in',
            'MAX(a.check_in_time) as last_check_in',
            'MAX(a.check_out_time) as last_check_out',
            'COUNT(a.id) as transaction_count',
            'a.attendance_date'
        ])
        ->where('a.attendance_date = :date')
        ->setParameter('date', $date)
        ->groupBy('a.application')
        ->addGroupBy('a.attendance_date')
        ->getQuery()
        ->getResult();
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

    public function existsByBiotimeTransactionId(string $transactionId): bool
{
    return (bool) $this->createQueryBuilder('a')
        ->select('COUNT(a.id)')
        ->andWhere('a.biotime_transaction_id = :tx')
        ->setParameter('tx', $transactionId)
        ->getQuery()
        ->getSingleScalarResult();
}


public function filterAttendanceQuery(
    ?KaabaApplication $applicant = null,
    ?KaabaInstitute $institute = null,
    ?string $status = null,
    ?\DateTimeInterface $date = null,
    ?array $managedInstitutes = null
) {
    $qb = $this->createQueryBuilder('a')
        ->join('a.application', 'app')
        ->leftJoin('a.institute', 'inst')
        ->orderBy('app.full_name', 'ASC')
        ->addOrderBy('a.attendance_date', 'DESC');

    if ($applicant) {
        $qb->andWhere('a.application = :applicant')
            ->setParameter('applicant', $applicant);
    }

    if ($institute) {
        $qb->andWhere('a.institute = :institute')
            ->setParameter('institute', $institute);
    } elseif ($managedInstitutes && !empty($managedInstitutes)) {
        $qb->andWhere('a.institute IN (:institutes)')
            ->setParameter('institutes', $managedInstitutes);
    }

    if ($status) {
        $qb->andWhere('a.status = :status')
            ->setParameter('status', $status);
    }

    if ($date) {
        $qb->andWhere('a.attendance_date = :date')
            ->setParameter('date', $date);
    }

    return $qb->getQuery();
}


}