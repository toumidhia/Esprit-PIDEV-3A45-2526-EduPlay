<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\Game; // Ajouté
use App\Entity\User; // Ajouté
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /**
     * Vérifie si un jeu est dans les favoris d'un utilisateur
     */
    public function isFavorite(User $user, Game $game): bool
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->andWhere('f.game = :game')
            ->setParameter('user', $user)
            ->setParameter('game', $game)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}