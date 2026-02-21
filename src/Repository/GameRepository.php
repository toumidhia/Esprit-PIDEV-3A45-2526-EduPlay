<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * Advanced search with filters
     *
     * filters possibles:
     * - search (string) : name ou description
     * - type (string)
     * - level (Level|int) : objet Level ou id
     *
     * sortBy possibles: id, name, type
     */
    public function getGameStatistics(): array
{
    $qb = $this->createQueryBuilder('g');

    return [
        // total
        'total' => $qb->select('COUNT(g.id)')
            ->getQuery()->getSingleScalarResult(),

         
      // 🔥 LEVEL LE PLUS UTILISÉ (nom)
    $topLevelRow = $this->createQueryBuilder('g')
        ->select('l.name AS levelName, COUNT(g.id) AS cnt')
        ->leftJoin('g.idLevel', 'l')
        ->groupBy('l.id, l.name')
        ->orderBy('cnt', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult(),
            // par type
            'type' => $qb->select('COUNT(DISTINCT g.type)')
            ->where('g.image IS NOT NULL')
            ->getQuery()->getSingleScalarResult(),

             // Top type (le plus utilisé)
    $topTypeRow = $this->createQueryBuilder('g')
        ->select('g.type AS type, COUNT(g.id) AS cnt')
        ->groupBy('g.type')
        ->orderBy('cnt', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult(),

        'topLevel' => $topLevelRow['levelName'] ?? '—',
        'top_type' => $topTypeRow['type'] ?? '-',
        
       
        
        

        
    ];
}

    public function findWithFilters(array $filters = [], string $sortBy = 'id', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.idLevel', 'l')
            ->addSelect('l');

        // Search by keyword (name or description)
        if (!empty($filters['search'])) {
            $qb->andWhere('g.name LIKE :search OR g.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $qb->andWhere('g.type LIKE :type')
               ->setParameter('type', '%' . $filters['type'] . '%');
        }

        // Filter by level (accept Level object OR level id)
        if (!empty($filters['level'])) {
            if ($filters['level'] instanceof Level) {
                $qb->andWhere('g.idLevel = :level')
                   ->setParameter('level', $filters['level']);
            } else {
                $qb->andWhere('l.id = :levelId')
                   ->setParameter('levelId', (int) $filters['level']);
            }
        }

        // Sorting
        $allowedSortFields = ['id', 'name', 'type'];
        if (in_array($sortBy, $allowedSortFields, true)) {
            $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
            $qb->orderBy('g.' . $sortBy, $sortOrder);
        } else {
            $qb->orderBy('g.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
    




    public function findWithFrontFilters(array $filters = [], string $sortBy = 'id', string $sortOrder = 'DESC'): array
{
    $qb = $this->createQueryBuilder('g')
        ->leftJoin('g.idLevel', 'l')
        ->addSelect('l');

    // search (name/description)
    if (!empty($filters['search'])) {
        $qb->andWhere('g.name LIKE :q OR g.description LIKE :q')
           ->setParameter('q', '%'.$filters['search'].'%');
    }

    // type
    if (!empty($filters['type'])) {
        $qb->andWhere('g.type LIKE :type')
           ->setParameter('type', '%'.$filters['type'].'%');
    }

    // difficulty (1..5) via Level
    if (!empty($filters['difficulty'])) {
        $qb->andWhere('l.difficulty = :diff')
           ->setParameter('diff', (int) $filters['difficulty']);
    }

    // sorting autorisé
    $allowedSort = ['id', 'name', 'type'];
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

    if (in_array($sortBy, $allowedSort, true)) {
        $qb->orderBy('g.' . $sortBy, $sortOrder);
    } else {
        $qb->orderBy('g.id', 'DESC');
    }

    return $qb->getQuery()->getResult();
}

public function findChildEmails(): array
{
    $conn = $this->getEntityManager()->getConnection();

    $sql = '
        SELECT email
        FROM `user`
        WHERE email IS NOT NULL
          AND JSON_CONTAINS(roles, :role) = 1
    ';

    return $conn->fetchFirstColumn($sql, [
        'role' => '"ROLE_PARENT"',
    ]);
}




}
