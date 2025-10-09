<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\MetierChat;
use App\Entity\MetierBlockedUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<MetierChat>
 */

class MetierChatRepository extends ServiceEntityRepository

{
    private $em;
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $em)
    {
        parent::__construct($registry, MetierChat::class);
        $this->em = $em;
    }

    //    /**
    //     * @return MetierChat[] Returns an array of MetierChat objects
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

    //    public function findOneBySomeField($value): ?MetierChat
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findContactsByUser(User $user): array
    {
        // Step 1: Retrieve all blocked users by the current user
        $blockedUserRepository = $this->em->getRepository(MetierBlockedUser::class);
        $blocker_users = $blockedUserRepository->createQueryBuilder('bu')
            ->select('IDENTITY(bu.blocked_by)')
            ->where('bu.blocked_user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();
        
        // Convert blocked user IDs into a simple array
        $blockers = array_map('current', $blocker_users);
    
        // Step 2: Retrieve all contacts, excluding blocked ones
        $qb = $this->createQueryBuilder('mc');
    
        $qb->select('CASE WHEN s = :user THEN r ELSE s END AS contact')
            ->join('mc.sender', 's')
            ->join('mc.receiver', 'r')
            ->where('s = :user OR r = :user')
            ->setParameter('user', $user)
            ->andWhere('s.id NOT IN (:blockers) AND r.id NOT IN (:blockers)')
            ->setParameter('blockers', implode(',', $blockers))
            ->groupBy('contact');
    
        $result = $qb->getQuery()->getResult();
        // dump($result);
        // Extracting the actual User entities from the result
        $contactIds = array_map(function ($item) {
            return $item['contact'];
        }, $result);
    
        // Fetch the User entities based on the IDs
        if (!empty($contactIds)) {
            $userRepository = $this->em->getRepository(User::class);
            $contacts = $userRepository->findBy(['id' => $contactIds]);
        } else {
            $contacts = [];
        }
    
        return $contacts;
    }
    


    public function findChatsBetweenUsers(User $user1, User $user2): array
    {
        $qb = $this->createQueryBuilder('mc');

        $qb->andWhere('mc.sender = :user1 AND mc.receiver = :user2')
            ->orWhere('mc.sender = :user2 AND mc.receiver = :user1')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('mc.date', 'ASC'); // Assuming you have a 'createdAt' field to order the chats
        
        return $qb->getQuery()->getResult();
    }

    public function findLastChatBetweenUsers(User $currentUser, User $clientUser): ?MetierChat
    {
        $qb = $this->createQueryBuilder('mc');

        return $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('mc.sender', ':currentUser'),
                        $qb->expr()->eq('mc.receiver', ':clientUser')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('mc.sender', ':clientUser'),
                        $qb->expr()->eq('mc.receiver', ':currentUser')
                    )
                )
            )
            ->setParameter('currentUser', $currentUser)
            ->setParameter('clientUser', $clientUser)
            ->orderBy('mc.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
