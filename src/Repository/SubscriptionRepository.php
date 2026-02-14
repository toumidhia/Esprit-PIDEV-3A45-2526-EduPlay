<?php
// src/Repository/SubscriptionRepository.php

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
     * Check if a specific parent has subscribed a specific kid to a specific course
     */
    public function isSubscribed(int $parentId, int $kidId, int $courseId): bool
    {
        return $this->createQueryBuilder('s')
                ->select('COUNT(s.id)')
                ->where('s.parent = :parentId')
                ->andWhere('s.kid = :kidId')
                ->andWhere('s.course = :courseId')
                ->andWhere('s.active = :active')
                ->setParameter('parentId', $parentId)
                ->setParameter('kidId', $kidId)
                ->setParameter('courseId', $courseId)
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult() > 0;
    }

    /**
     * Get course IDs that a kid is subscribed to
     */
    public function getKidCourseIds(int $kidId): array
    {
        $result = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.course) as courseId')
            ->where('s.kid = :kidId')
            ->andWhere('s.active = :active')
            ->setParameter('kidId', $kidId)
            ->setParameter('active', true)
            ->getQuery()
            ->getScalarResult();

        return array_map(function($item) {
            return $item['courseId'];
        }, $result);
    }

    /**
     * Find active subscriptions for a specific kid
     */
    public function findActiveSubscriptionsByKid(int $kidId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.course', 'c')
            ->addSelect('c')
            ->where('s.kid = :kidId')
            ->andWhere('s.active = :active')
            ->setParameter('kidId', $kidId)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a kid is subscribed to a course
     */
    public function isKidSubscribedToCourse(int $kidId, int $courseId): bool
    {
        $subscription = $this->findOneBy([
            'kid' => $kidId,
            'course' => $courseId,
            'active' => true
        ]);

        return $subscription !== null;
    }

    /**
     * Count active subscriptions for a course
     */
    public function countActiveSubscriptions(int $courseId): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.course = :courseId')
            ->andWhere('s.active = :active')
            ->setParameter('courseId', $courseId)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find subscription by kid and course
     */
    public function findSubscriptionByKidAndCourse(int $kidId, int $courseId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.kid = :kidId')
            ->andWhere('s.course = :courseId')
            ->andWhere('s.active = :active')
            ->setParameter('kidId', $kidId)
            ->setParameter('courseId', $courseId)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get subscription statistics for a course
     */
    public function getSubscriptionStatsByCourse(int $courseId): array
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id) as total')
            ->addSelect('SUM(CASE WHEN s.active = true THEN 1 ELSE 0 END) as active')
            ->where('s.course = :courseId')
            ->setParameter('courseId', $courseId)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Find active subscriptions for a parent's kids
     */
    public function findActiveSubscriptionsByParent(int $parentId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.parent', 'p')
            ->join('s.course', 'c')
            ->join('s.kid', 'k')
            ->where('p.id = :parentId')
            ->andWhere('s.active = :active')
            ->setParameter('parentId', $parentId)
            ->setParameter('active', true)
            ->orderBy('s.subscribedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count enrollments by age range for a course
     */
    public function countEnrollmentsByAgeRange(int $courseId, int $minAge, int $maxAge): int
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.course', 'c')
            ->join('s.kid', 'k')
            ->where('c.id = :courseId')
            ->andWhere('s.active = :active')
            ->andWhere('k.birthDate IS NOT NULL')
            ->andWhere('DATE_DIFF(:now, k.birthDate) / 365 BETWEEN :minAge AND :maxAge')
            ->setParameter('courseId', $courseId)
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->setParameter('minAge', $minAge)
            ->setParameter('maxAge', $maxAge)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get age distribution for a course
     */
    public function getAgeDistribution(int $courseId): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('s')
            ->select('FLOOR(DATE_DIFF(:now, k.birthDate) / 365) as age, COUNT(s.id) as count')
            ->join('s.course', 'c')
            ->join('s.kid', 'k')
            ->where('c.id = :courseId')
            ->andWhere('s.active = :active')
            ->andWhere('k.birthDate IS NOT NULL')
            ->setParameter('courseId', $courseId)
            ->setParameter('active', true)
            ->setParameter('now', $now)
            ->groupBy('age')
            ->orderBy('age', 'ASC')
            ->getQuery()
            ->getResult();
    }
}