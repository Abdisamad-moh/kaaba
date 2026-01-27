<?php

namespace App\Repository;

use App\Entity\KaabaStudentDevice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KaabaStudentDeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KaabaStudentDevice::class);
    }

    public function findEnrolledStudentsByInstitute($instituteId)
    {
        return $this->createQueryBuilder('sd')
            ->join('sd.application', 'a')
            ->join('a.institute', 'i')
            ->where('i.id = :instituteId')
            ->andWhere('sd.enrollment_status IN (:statuses)')
            ->setParameter('instituteId', $instituteId)
            ->setParameter('statuses', ['enrolled', 'active'])
            ->orderBy('a.full_name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findStudentDeviceByApplication($applicationId)
    {
        return $this->createQueryBuilder('sd')
            ->join('sd.application', 'a')
            ->where('a.id = :applicationId')
            ->setParameter('applicationId', $applicationId)
            ->getQuery()
            ->getOneOrNullResult();
    }
    // App\Repository\KaabaApplicationRepository.php
   public function findEnrolledStudentsWithDevices(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.institute', 'i')
            ->leftJoin('a.course', 'c')
            ->innerJoin('a.studentDevices', 'sd')
            ->where('a.isEnrolledInBioTime = :enrolled')
            ->andWhere('sd.enrollment_status = :enrollmentStatus')
            ->setParameter('enrolled', true)
            ->setParameter('enrollmentStatus', 'enrolled')
            ->orderBy('a.full_name', 'ASC');

        // Apply filters
        if (!empty($filters['institute'])) {
            $qb->andWhere('a.institute = :institute')
                ->setParameter('institute', $filters['institute']);
        }

        if (!empty($filters['course'])) {
            $qb->andWhere('a.course = :course')
                ->setParameter('course', $filters['course']);
        }

        if (!empty($filters['employee_code'])) {
            $qb->andWhere('a.biotimeEmployeeCode LIKE :employeeCode')
                ->setParameter('employeeCode', '%' . $filters['employee_code'] . '%');
        }

        if (!empty($filters['enrollment_date_from'])) {
            $qb->andWhere('sd.enrollment_date >= :dateFrom')
                ->setParameter('dateFrom', $filters['enrollment_date_from']);
        }

        if (!empty($filters['enrollment_date_to'])) {
            $qb->andWhere('sd.enrollment_date <= :dateTo')
                ->setParameter('dateTo', $filters['enrollment_date_to']);
        }

        return $qb->getQuery()->getResult();
    }

    // App\Repository\KaabaStudentDeviceRepository.php
public function findEnrolledWithFilters(array $filters = [])
{
    $qb = $this->createQueryBuilder('sd')
        ->innerJoin('sd.application', 'a')
        ->leftJoin('a.institute', 'i')
        ->leftJoin('a.course', 'c')
        ->leftJoin('sd.device', 'd')
        ->leftJoin('d.area', 'area')
        ->where('sd.enrollment_status = :enrollmentStatus')
        ->andWhere('a.isEnrolledInBioTime = :enrolled')
        ->setParameter('enrollmentStatus', 'enrolled')
        ->setParameter('enrolled', true)
        ->orderBy('a.full_name', 'ASC');

    // Apply filters
    if (!empty($filters['institute'])) {
        $qb->andWhere('a.institute = :institute')
            ->setParameter('institute', $filters['institute']);
    }

    if (!empty($filters['course'])) {
        $qb->andWhere('a.course = :course')
            ->setParameter('course', $filters['course']);
    }

    if (!empty($filters['employee_code'])) {
        $qb->andWhere('a.biotimeEmployeeCode LIKE :employeeCode')
            ->setParameter('employeeCode', '%' . $filters['employee_code'] . '%');
    }

    if (!empty($filters['enrollment_date_from'])) {
        $qb->andWhere('sd.enrollment_date >= :dateFrom')
            ->setParameter('dateFrom', $filters['enrollment_date_from']);
    }

    if (!empty($filters['enrollment_date_to'])) {
        $qb->andWhere('sd.enrollment_date <= :dateTo')
            ->setParameter('dateTo', $filters['enrollment_date_to']);
    }

    return $qb->getQuery()->getResult();
}
}