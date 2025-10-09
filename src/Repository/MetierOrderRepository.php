<?php

namespace App\Repository;

use App\Entity\MetierOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetierOrder>
 */
class MetierOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetierOrder::class);
    }

        /**
     * @return MetierOrder[] Returns an array of Project objects
     */
    public function filterOrders(
        $customer = null,
        $status = null,
        $type = null,
        $product = null
    ): array {

          // Normalize to ensure full-day coverage
        //   $startOfDay = Carbon::instance($from_date)->startOfDay()->toDateTime();
        //   $endOfDay = Carbon::instance($to_date)->endOfDay()->toDateTime();
        $qb = $this->createQueryBuilder('p');
        // Ensure we exclude deleted projects
        // $qb->where('p.is_deleted = :is_deleted')
        //     ->setParameter('is_deleted', false);

        // Apply filters as needed
        if ($type) {
            $qb->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }
        if ($status) {
            $qb->andWhere('p.payment_status = :status')
                ->setParameter('status', $status);
        }
        if ($customer) {
                $qb->andWhere('p.customer = :customer')
                ->setParameter('customer', $customer);
        }
        if ($product) {
                $qb->andWhere('p.plan = :product')
                ->setParameter('product', $product);
        }

        $qb->orderBy('p.order_date', 'DESC');
       
        // Add ordering by createdAt in descending order
        // $qb->orderBy('p.createdAt', 'DESC');

        // Debugging: Output SQL for verification
        $query = $qb->getQuery();
        // dd($query->getSQL(), $query->getParameters());

        return $query->getResult();
    }

    //    /**
    //     * @return MetierOrder[] Returns an array of MetierOrder objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?MetierOrder
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
