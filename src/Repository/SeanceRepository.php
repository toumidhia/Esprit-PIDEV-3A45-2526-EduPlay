<?php

namespace App\Repository;

use App\Entity\Seance;
use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 */
class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

    /**
     * Find sessions with filters
     * 
     * @param array $filters Filter criteria
     * @param string $sortBy Field to sort by
     * @param string $sortOrder ASC or DESC
     * @return Seance[]
     */
    public function findWithFilters(array $filters = [], string $sortBy = 'startTime', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.courseId', 'c')
            ->addSelect('c');

        // Filter by course
        if (!empty($filters['course']) && $filters['course'] instanceof Course) {
            $qb->andWhere('s.courseId = :course')
               ->setParameter('course', $filters['course']);
        }

        // Filter by course ID
        if (!empty($filters['courseId'])) {
            $qb->andWhere('s.courseId = :courseId')
               ->setParameter('courseId', $filters['courseId']);
        }

        // Filter by date range - start date
        if (!empty($filters['startDate'])) {
            $qb->andWhere('s.startTime >= :startDate')
               ->setParameter('startDate', new \DateTime($filters['startDate']));
        }

        // Filter by date range - end date
        if (!empty($filters['endDate'])) {
            $qb->andWhere('s.endTime <= :endDate')
               ->setParameter('endDate', new \DateTime($filters['endDate']));
        }

        // Filter by month
        if (!empty($filters['month'])) {
            $month = new \DateTime($filters['month'] . '-01');
            $qb->andWhere('s.startTime >= :monthStart')
               ->andWhere('s.startTime < :monthEnd')
               ->setParameter('monthStart', $month)
               ->setParameter('monthEnd', $month->modify('+1 month'));
        }

        // Search by course title
        if (!empty($filters['search'])) {
            $qb->andWhere('c.title LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Sorting
        $allowedSortFields = ['id', 'startTime', 'endTime'];
        if (in_array($sortBy, $allowedSortFields)) {
            $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy('s.' . $sortBy, $sortOrder);
        } else {
            $qb->orderBy('s.startTime', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find upcoming sessions
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.startTime > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find past sessions
     */
    public function findPast(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.endTime < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get session statistics
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('s');
        $now = new \DateTime();

        return [
            'total' => $qb->select('COUNT(s.id)')->getQuery()->getSingleScalarResult(),
            'upcoming' => $qb->select('COUNT(s.id)')->where('s.startTime > :now')
                ->setParameter('now', $now)->getQuery()->getSingleScalarResult(),
            'past' => $qb->select('COUNT(s.id)')->where('s.endTime < :now')
                ->setParameter('now', $now)->getQuery()->getSingleScalarResult(),
        ];
    }
}
