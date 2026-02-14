<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    /**
     * Advanced search with filters
     *
     * @param array $filters Array of filter criteria
     * @param string $sortBy Field to sort by
     * @param string $sortOrder ASC or DESC
     * @return Course[]
     */
    public function findWithFilters(array $filters = [], string $sortBy = 'id', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.teacherId', 't')
            ->addSelect('t');

        // Search by keyword (title or description)
        if (!empty($filters['search'])) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filter by level
        if (!empty($filters['level'])) {
            $qb->andWhere('c.level = :level')
                ->setParameter('level', $filters['level']);
        }

        // Filter by teacher
        if (!empty($filters['teacher']) && $filters['teacher'] instanceof User) {
            $qb->andWhere('c.teacherId = :teacher')
                ->setParameter('teacher', $filters['teacher']);
        }

        // Filter by duration (contains search)
        if (!empty($filters['duration'])) {
            $qb->andWhere('c.durationTraining LIKE :duration')
                ->setParameter('duration', '%' . $filters['duration'] . '%');
        }

        // Sorting
        $allowedSortFields = ['id', 'title', 'level', 'status', 'durationTraining'];
        if (in_array($sortBy, $allowedSortFields)) {
            $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy('c.' . $sortBy, $sortOrder);
        } else {
            $qb->orderBy('c.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find courses by teacher
     */
    public function findByTeacher(User $teacher, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.teacherId = :teacher')
            ->setParameter('teacher', $teacher);

        // Add filter logic if needed
        if (!empty($filters['search'])) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find only accepted courses
     */
    public function findAccepted(array $filters = []): array
    {
        $filters['status'] = 'accepted';
        return $this->findWithFilters($filters);
    }

    /**
     * Get statistics for dashboard - THIS WAS MISSING!
     */
    public function getCourseStatistics(): array
    {
        // Get total courses
        $totalQuery = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery();
        $total = $totalQuery->getSingleScalarResult();

        // Get pending courses
        $pendingQuery = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery();
        $pending = $pendingQuery->getSingleScalarResult();

        // Get accepted courses
        $acceptedQuery = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'accepted')
            ->getQuery();
        $accepted = $acceptedQuery->getSingleScalarResult();

        // Get rejected courses
        $rejectedQuery = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status = :status')
            ->setParameter('status', 'rejected')
            ->getQuery();
        $rejected = $rejectedQuery->getSingleScalarResult();

        return [
            'total' => (int) $total,
            'pending' => (int) $pending,
            'accepted' => (int) $accepted,
            'rejected' => (int) $rejected,
        ];
    }

    /**
     * Find courses for parents: accepted courses with at least one seance
     */
    public function findForParents(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.teacherId', 't')
            ->addSelect('t')
            ->leftJoin('c.seances', 's')  // Changed: using 'c.seances' (the property name)
            ->where('c.status = :status')
            ->andWhere('s.id IS NOT NULL')
            ->setParameter('status', 'accepted');

        // Apply search filters
        if (!empty($filters['search'])) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['level'])) {
            $qb->andWhere('c.level = :level')
                ->setParameter('level', $filters['level']);
        }

        $qb->groupBy('c.id')
            ->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find courses for kids: only courses their parent subscribed them to
     */
    public function findForKid(array $courseIds, array $filters = []): array
    {
        if (empty($courseIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.teacherId', 't')
            ->addSelect('t')
            ->where('c.id IN (:courseIds)')
            ->andWhere('c.status = :status')
            ->setParameter('courseIds', $courseIds)
            ->setParameter('status', 'accepted');

        // Apply search filters
        if (!empty($filters['search'])) {
            $qb->andWhere('c.title LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['level'])) {
            $qb->andWhere('c.level = :level')
                ->setParameter('level', $filters['level']);
        }

        $qb->orderBy('c.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find pending courses for admin dashboard
     */
    public function findPendingCourses(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.teacherId', 't')
            ->addSelect('t')
            ->where('c.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('c.id', 'DESC')  // Changed from 'c.createdAt'
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent courses
     */
    public function findRecentCourses(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.teacherId', 't')
            ->addSelect('t')
            ->orderBy('c.id', 'DESC')  // Changed from 'c.createdAt'
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}