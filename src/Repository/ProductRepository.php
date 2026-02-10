<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function searchFilterSort(?string $search, ?bool $availability, string $sortBy = 'id', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p');
        if ($search !== null && $search !== '') {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($availability !== null) {
            $qb->andWhere('p.availability = :availability')
                ->setParameter('availability', $availability);
        }
        $allowedSort = ['id', 'name', 'price', 'description', 'availability'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        $qb->orderBy('p.' . $sortBy, $sortOrder === 'ASC' ? 'ASC' : 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function countAvailable(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.availability = :true')
            ->setParameter('true', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnavailable(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.availability = :false')
            ->setParameter('false', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Product[] For frontend shop
     */
    public function searchFilterSortFront(?string $search, ?float $priceMin, ?float $priceMax, string $sortBy = 'id', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.availability = :true')
            ->setParameter('true', true);
        if ($search !== null && $search !== '') {
            $qb->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($priceMin !== null && $priceMin > 0) {
            $qb->andWhere('p.price >= :priceMin')
                ->setParameter('priceMin', $priceMin);
        }
        if ($priceMax !== null && $priceMax > 0) {
            $qb->andWhere('p.price <= :priceMax')
                ->setParameter('priceMax', $priceMax);
        }
        $allowedSort = ['id', 'name', 'price'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }
        $qb->orderBy('p.' . $sortBy, $sortOrder === 'ASC' ? 'ASC' : 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getStatsFront(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as total, MIN(p.price) as minPrice, MAX(p.price) as maxPrice, AVG(p.price) as avgPrice')
            ->andWhere('p.availability = :true')
            ->setParameter('true', true);
        $result = $qb->getQuery()->getSingleResult();
        return [
            'total' => (int) ($result['total'] ?? 0),
            'minPrice' => (float) ($result['minPrice'] ?? 0),
            'maxPrice' => (float) ($result['maxPrice'] ?? 0),
            'avgPrice' => (float) ($result['avgPrice'] ?? 0),
        ];
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
