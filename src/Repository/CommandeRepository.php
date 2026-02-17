<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * @return Commande[]
     */
    public function searchFilterSort(
        ?string $search,
        ?\DateTimeInterface $dateFrom,
        ?\DateTimeInterface $dateTo,
        ?int $userId,
        ?int $productId,
        string $sortBy = 'dateCommande',
        string $sortOrder = 'DESC',
        bool $paidOnly = true
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.product', 'pr');
        
        // Filter to show only paid commandes by default
        if ($paidOnly) {
            $qb->andWhere('c.isPaid = true');
        }
        
        if ($search !== null && $search !== '') {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR pr.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($dateFrom !== null) {
            $qb->andWhere('c.dateCommande >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }
        if ($dateTo !== null) {
            $qb->andWhere('c.dateCommande <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }
        if ($userId !== null && $userId > 0) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }
        if ($productId !== null && $productId > 0) {
            $qb->andWhere('pr.id = :productId')
                ->setParameter('productId', $productId);
        }
        $allowedSort = ['id', 'dateCommande', 'quantity', 'totalAmount'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'dateCommande';
        }
        $qb->orderBy('c.' . $sortBy, $sortOrder === 'ASC' ? 'ASC' : 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function totalAmountSum(): int
    {
        $v = $this->createQueryBuilder('c')
            ->select('SUM(c.totalAmount)')
            ->getQuery()
            ->getSingleScalarResult();
        return (int) ($v ?? 0);
    }

    public function totalAmountSumLastDays(int $days): float
    {
        $since = new \DateTime(sprintf('-%d days', $days));
        $v = $this->createQueryBuilder('c')
            ->select('SUM(c.totalAmount)')
            ->andWhere('c.dateCommande >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($v ?? 0);
    }

    public function countCommandesLastDays(int $days): int
    {
        $since = new \DateTime(sprintf('-%d days', $days));
        $v = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.dateCommande >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($v ?? 0);
    }

    public function countCommandes(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
