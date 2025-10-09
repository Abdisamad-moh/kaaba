<?php

namespace App\Repository;

use App\Entity\EmployerTender;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<EmployerTender>
 */
class EmployerTenderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmployerTender::class);
    }

    //    /**
    //     * @return EmployerTender[] Returns an array of EmployerTender objects
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

    //    public function findOneBySomeField($value): ?EmployerTender
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
                        ->setParameter('status', true)
                        ->andWhere('j.closing_date >= :dateNow') // Filter based on expiry
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
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.status = :status')
            ->setParameter('status', 'posted')
            ->andWhere('t.closing_date >= :dateNow') // Filter based on expiry
            ->setParameter('dateNow', new \DateTime());
        // TODO Ask about statuses;


        if ($title) {
            $qb->andWhere('t.title LIKE :title')
            ->setParameter('title', '%' . $title . '%');
        }
        
        if ($country)
            $qb->leftJoin('t.country', 'ct')
                ->andWhere('ct.id = :countryId')
                ->setParameter('countryId', $country);

        if ($city)
            $qb->leftJoin('t.city', 'city')
            ->andWhere('city.id = :cityId')
            ->setParameter('cityId', $city);


        // Clone the query builder to get a count of all results without pagination
        $countQb = clone $qb;
        $count = (int) $countQb->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Apply pagination to the original query
        $jobs = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        
        return [
            'tenders' => $jobs,
            'total' => $count,
        ];
    }

      /**
     * @return EmployerTender[] Returns an array of AcademySemesterOffering objects
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
