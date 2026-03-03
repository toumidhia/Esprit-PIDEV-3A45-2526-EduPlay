<?php

namespace App\Repository;

use App\Entity\Game;
use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Favorite;
use Doctrine\ORM\QueryBuilder;

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
 * @return array<int|string, mixed>
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

        'topLevel' => (string) ($topLevelRow['levelName'] ?? '—'),

        'top_type' => (string) ($topTypeRow['type'] ?? '-'),
       
        
        

        
    ];
}


/**
 * @param array<string, mixed> $filters
 * @return Game[]               # retourne un tableau d’objets Game
 */
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
    




   
/**
 * @return string[]
 */
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




/**
 * @param int|null $age
 * @param array<string, mixed> $filters
 * @param string $sortBy
 * @param string $sortOrder
 * @param object|null $user
 */
public function findPlayableForAgeQB(?int $age, array $filters = [], string $sortBy = 'id', string $sortOrder = 'DESC', $user = null): QueryBuilder
{
    $qb = $this->createQueryBuilder('g')
        ->leftJoin('g.idLevel', 'l')
        ->addSelect('l');

    // Filtre âge
    if ($age !== null) {
        $qb->andWhere(':age BETWEEN l.minAge AND l.maxAge')
           ->setParameter('age', $age);
    }

    // Filtres existants
    if (!empty($filters['search'])) {
        $qb->andWhere('g.name LIKE :q OR g.description LIKE :q')
           ->setParameter('q', '%'.$filters['search'].'%');
    }

    if (!empty($filters['type'])) {
        $qb->andWhere('g.type LIKE :type')
           ->setParameter('type', '%'.$filters['type'].'%');
    }

    if (!empty($filters['difficulty'])) {
        $qb->andWhere('l.difficulty = :diff')
           ->setParameter('diff', (int) $filters['difficulty']);
    }
    // ✅ Filtre "Favoris seulement"
if (!empty($filters['favoritesOnly']) && $user) {
    $qb->innerJoin(Favorite::class, 'f', 'WITH', 'f.game = g')
       ->andWhere('f.user = :favUser')
       ->setParameter('favUser', $user);
}

    // Tri
    $allowedSort = ['id', 'name', 'type'];
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

    if (in_array($sortBy, $allowedSort, true)) {
        $qb->orderBy('g.' . $sortBy, $sortOrder);
    } else {
        $qb->orderBy('g.id', 'DESC');
    }

    return $qb; // ✅ IMPORTANT (pas de getResult ici)
}
}
