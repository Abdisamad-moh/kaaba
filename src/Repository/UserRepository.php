<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\MetierOrder;
use App\Entity\EmployerStaff;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function searchAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.type = :val')
            ->setParameter('val', "admin")
            ->orderBy('u.id', 'ASC')
            //    ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[] Returns an array of Project objects
     */
    public function filterAccounts(
        $type = null,
        $status = null,
        $email = null,
        $verification = null
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
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }
        if ($email) {
                $qb->andWhere('p.email = :email')
                ->setParameter('email', $email);
        }
        if ($verification) {
                $qb->andWhere('p.isVerified = :verification')
                ->setParameter('verification', $verification);
        }
       
        // Add ordering by createdAt in descending order
        $qb->orderBy('p.createdAt', 'DESC');

        // Debugging: Output SQL for verification
        $query = $qb->getQuery();
        // dd($query->getSQL(), $query->getParameters());

        return $query->getResult();
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function searchJobseekers($jobTitle = null, $country = null, $state = null, $city = null, $experience = null, $education = null, $careerStatus = null)
    {
        $qb = $this->createQueryBuilder('j');

        $qb->join('j.jobseekerDetails', 'jd')
            ->join('j.jobSeekerResume', 'jr');
       
        if ($jobTitle) {
            $qb->andWhere('jr.jobTitle = :jobTitle')
                ->setParameter('jobTitle', $jobTitle);
        }

        if ($city) {
            $qb->andWhere('jd.city = :city')
                ->setParameter('city', $city);
        }
        if ($country) {
            $qb->andWhere('jd.country = :country')
                ->setParameter('country', $country);
        }
        if ($careerStatus) {
            $qb->andWhere('jd.careerStatus = :careerStatus')
                ->setParameter('careerStatus', $careerStatus);
        }
        if ($state) {
            $qb->andWhere('jd.state = :state')
                ->setParameter('state', $state);
        }

        if ($experience) {
            $qb->andWhere('jr.experience = :experience')
                ->setParameter('experience', $experience);
        }

        if ($education) {
            $qb->andWhere('jr.education = :education')
                ->setParameter('education', $education);
        }
        $qb->andWhere('jr.publicProfile = :pprofile')
        ->setParameter('pprofile', 1);

        return $qb->getQuery()->getResult();
    }

    public function findOneByResetToken(string $plainToken): ?User
    {
        // Retrieve all users with a non-null reset token
        $users = $this->createQueryBuilder('u')
            ->where('u.reset_token IS NOT NULL')
            ->getQuery()
            ->getResult();

        // Iterate over users to check the token
        foreach ($users as $user) {
            // Use password_verify to compare the plain token with the hashed token
            if (password_verify($plainToken, $user->getResetToken())) {
                return $user; // Return the user if the token matches
            }
        }

        // Return null if no user was found with the matching token
        return null;
    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Get internal staff members for the given employer (user).
     *
     * @param User $employer
     * @return EmployerStaff[]
     */
    public function findInternalStaffByEmployer(User $employer): array
    {
        return $this->_em->createQueryBuilder()
            ->select('s')
            ->from(EmployerStaff::class, 's')
            ->where('s.employer = :employer')
            // Uncomment the line below if you have an `isInternal` flag or similar
            // ->andWhere('s.isInternal = true')
            ->setParameter('employer', $employer)
            ->getQuery()
            ->getResult();
    }

    public function findActiveSubscription(User $user): ?MetierOrder
{
    return $this->getEntityManager()->createQueryBuilder()
        ->select('o')
        ->from(MetierOrder::class, 'o')
        ->where('o.customer = :user')
        ->andWhere('o.valid_from <= :now')
        ->andWhere('o.valid_to >= :now')
        ->andWhere('o.category = :category')
        ->andWhere('o.customer_type = :customerType')
        ->setParameter('user', $user)
        ->setParameter('now', new \DateTimeImmutable())
        ->setParameter('category', 'jobseeker')
        ->setParameter('customerType', 'subscription')
        ->orderBy('o.valid_to', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
    
}
