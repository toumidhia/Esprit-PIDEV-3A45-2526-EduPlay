<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Check if a parent has subscribed a kid to a course
     */
    public function isSubscribed(int $parentId, int $kidId, int $courseId): bool
    {
        $result = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.parentId = :parentId')
            ->andWhere('s.kidId = :kidId')
            ->andWhere('s.courseId = :courseId')
            ->andWhere('s.active = true')
            ->setParameter('parentId', $parentId)
            ->setParameter('kidId', $kidId)
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Get all course IDs that a kid has access to (via parent subscriptions)
     */
    public function getKidCourseIds(int $kidId): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.courseId) as courseId')
            ->where('s.kidId = :kidId')
            ->andWhere('s.active = true')
            ->setParameter('kidId', $kidId)
            ->getQuery()
            ->getResult();

        return array_map(fn($r) => $r['courseId'], $results);
    }

    /**
     * Get parent's kids who can be subscribed to courses
     */
    public function getParentKids(int $parentId): array
    {
        return $this->createQueryBuilder('s')
            ->select('DISTINCT IDENTITY(s.kidId)')
            ->where('s.parentId = :parentId')
            ->setParameter('parentId', $parentId)
            ->getQuery()
            ->getResult();
    }
}
