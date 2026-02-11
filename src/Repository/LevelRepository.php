<?php

namespace App\Repository;

use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Level::class);
    }

    /**
     * STATISTIQUES Level (style GameStatistics)
     */
    public function getLevelStatistics(): array
    {
        // total levels
        $total = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // niveaux sans jeux (LEFT JOIN + WHERE g.id IS NULL)
        $withoutGames = (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->leftJoin('App\Entity\Game', 'g', 'WITH', 'g.idLevel = l')
            ->where('g.id IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // top level (le plus utilisé) = level.name avec nb games
        $topLevelRow = $this->createQueryBuilder('l')
            ->select('l.name AS levelName, COUNT(g.id) AS cnt')
            ->leftJoin('App\Entity\Game', 'g', 'WITH', 'g.idLevel = l')
            ->groupBy('l.id, l.name')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // difficulté moyenne (optionnel mais logique)
        $avgDifficulty = $this->createQueryBuilder('l')
            ->select('AVG(l.difficulty) AS avgDiff')
            ->getQuery()
            ->getOneOrNullResult();

        // min/max age global (optionnel)
        $ageRow = $this->createQueryBuilder('l')
            ->select('MIN(l.minAge) AS minAge, MAX(l.maxAge) AS maxAge')
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'total' => $total,
            'withoutGames' => $withoutGames,
            'withGames' => $total - $withoutGames,

            'topLevel' => $topLevelRow['levelName'] ?? '—',
            'topLevelCount' => isset($topLevelRow['cnt']) ? (int) $topLevelRow['cnt'] : 0,

            'avgDifficulty' => isset($avgDifficulty['avgDiff']) ? (float) $avgDifficulty['avgDiff'] : 0.0,
            'minAge' => isset($ageRow['minAge']) ? (int) $ageRow['minAge'] : null,
            'maxAge' => isset($ageRow['maxAge']) ? (int) $ageRow['maxAge'] : null,
            
        ];
    }

    /**
     * FILTRES Level (comme findWithFilters de Game)
     *
     * filters possibles:
     * - search (string): name/description/pedagGoal
     * - difficulty (int)
     * - minAge (int)
     * - maxAge (int)
     * - hasGames (0|1)
     *
     * sortBy possibles: id, name, difficulty, minAge, maxAge, createdAt
     */
    public function findWithFilters(array $filters = [], string $sortBy = 'id', string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('l');

        // search
        if (!empty($filters['search'])) {
            $qb->andWhere('l.name LIKE :q OR l.description LIKE :q OR l.pedagGoal LIKE :q')
               ->setParameter('q', '%' . $filters['search'] . '%');
        }

        // difficulty exact
        if (!empty($filters['difficulty'])) {
            $qb->andWhere('l.difficulty = :diff')
               ->setParameter('diff', (int) $filters['difficulty']);
        }

        // age range
        if (!empty($filters['minAge'])) {
            $qb->andWhere('l.minAge >= :minAge')
               ->setParameter('minAge', (int) $filters['minAge']);
        }

        if (!empty($filters['maxAge'])) {
            $qb->andWhere('l.maxAge <= :maxAge')
               ->setParameter('maxAge', (int) $filters['maxAge']);
        }

        // hasGames: 1 => avec jeux, 0 => sans jeux
        if (isset($filters['hasGames']) && $filters['hasGames'] !== '') {
            $qb->leftJoin('App\Entity\Game', 'g', 'WITH', 'g.idLevel = l');

            if ((int) $filters['hasGames'] === 1) {
                $qb->andWhere('g.id IS NOT NULL');
            } else {
                $qb->andWhere('g.id IS NULL');
            }
        }

        // sorting
        $allowedSortFields = ['id', 'name', 'difficulty', 'minAge', 'maxAge', 'createdAt'];
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        if (in_array($sortBy, $allowedSortFields, true)) {
            $qb->orderBy('l.' . $sortBy, $sortOrder);
        } else {
            $qb->orderBy('l.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
    public function findByFiltersFront(array $filters = [], string $sortBy = 'id', string $sortOrder = 'DESC'): array
{
    $qb = $this->createQueryBuilder('g')
        ->leftJoin('g.idLevel', 'l')
        ->addSelect('l');

    // search (name/description)
    if (!empty($filters['search'])) {
        $qb->andWhere('g.name LIKE :q OR g.description LIKE :q')
           ->setParameter('q', '%' . $filters['search'] . '%');
    }

    // type
    if (!empty($filters['type'])) {
        $qb->andWhere('g.type LIKE :type')
           ->setParameter('type', '%' . $filters['type'] . '%');
    }

    // difficulty (1..5) -> champ de Level
    if (!empty($filters['difficulty'])) {
        $qb->andWhere('l.difficulty = :diff')
           ->setParameter('diff', (int) $filters['difficulty']);
    }

    // tri
    $allowedSortFields = ['id', 'name', 'type'];
    $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

    if (in_array($sortBy, $allowedSortFields, true)) {
        $qb->orderBy('g.' . $sortBy, $sortOrder);
    } else {
        $qb->orderBy('g.id', 'DESC');
    }

    return $qb->getQuery()->getResult();
}

// src/Repository/GameRepository.php




}
