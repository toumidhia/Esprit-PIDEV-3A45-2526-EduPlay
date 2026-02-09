<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve un utilisateur par email OU username
     * Utilisé pour la connexion (parents utilisent email, enfants utilisent username)
     */
    public function findByEmailOrUsername(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier OR u.username = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les utilisateurs d'un type spécifique
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.type = :type')
            ->setParameter('type', $type)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les enfants d'un parent
     */
    public function findEnfantsByParent(User $parent): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.parent = :parent')
            ->andWhere('u.type = :type')
            ->setParameter('parent', $parent)
            ->setParameter('type', 'enfant')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}