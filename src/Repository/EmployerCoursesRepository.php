<?php

namespace App\Repository;

use App\Entity\EmployerCourses;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<EmployerCourses>
 */
class EmployerCoursesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployerCourses::class);
    }

    //    /**
    //     * @return EmployerCourses[] Returns an array of EmployerCourses objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?EmployerCourses
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getJobPaginator(int $offset, int $num_rows = 10, $single_scaler = false, $job_title = null, $country_id = null): Paginator
    {
        $now = new \DateTime();
        $queryBuilder = $this->createQueryBuilder('j');
        
        // Base query
        $queryBuilder->where('j.status = :status')
                     ->setParameter('status', 'posted')
                     ->andWhere('j.close_date >= :dateNow') // Filter based on expiry
                     ->setParameter('dateNow', $now);
    
        // Filtering by job title using LIKE
        if ($job_title) {
            $queryBuilder->andWhere('j.title LIKE :title')
                         ->setParameter('title', '%' . $job_title . '%');
        }
    
       
    
        // Filtering by country (relationship)
        if ($country_id != null) {

            $queryBuilder->leftJoin('j.country', 'ct') // Assuming 'country' is the relationship field
                         ->andWhere('ct.id = :country_id')
                         ->setParameter('country_id', $country_id);
        }
    
        if ($single_scaler) {
            $queryBuilder->select('count(j.id)');
        } else {
            $queryBuilder->setMaxResults($num_rows)
                         ->setFirstResult($offset)
                         ->orderBy('j.id', 'DESC');
        }

        $query = $queryBuilder->getQuery();
        
        return new Paginator($query);
        
    }

    public function findByFilters($limit, $offset, $country = null, $city = null, $title = null)
    {

        // dd($title);
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.status = :status')
            ->setParameter('status', 'posted')
            ->andWhere('c.close_date >= :dateNow') // Filter based on expiry
            ->setParameter('dateNow', new \DateTime());
        // TODO Ask about statuses;


        if ($title) {
            $qb->andWhere('c.title LIKE :title')
            ->setParameter('title', '%' . $title . '%');
        }
        
        if ($country)
            $qb->leftJoin('c.country', 'ct')
                ->andWhere('ct.id = :countryId')
                ->setParameter('countryId', $country);

        if ($city)
            $qb->leftJoin('c.city', 'city')
            ->andWhere('city.id = :cityId')
            ->setParameter('cityId', $city);

        // Clone the query builder to get a count of all results without pagination
        $countQb = clone $qb;
        $count = (int) $countQb->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination to the original query
        $courses = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        
        return [
            'courses' => $courses,
            'total' => $count,
        ];
    }

      /**
     * @return EmployerCourses[] Returns an array of AcademySemesterOffering objects
     */
    public function filter($status, $employer): array
    {
        $data = $this->createQueryBuilder('a');
        $data->andWhere('a.status !=  :id')->setParameter('id', "deleted");
        $data->andWhere('a.employer =  :emp')->setParameter('emp', $employer);

        if ($status) {
            $data->andWhere('a.status =  :status')->setParameter('status', $status);
        }
        $data = $data->getQuery()->getResult();

        return $data;
    }
    
}
