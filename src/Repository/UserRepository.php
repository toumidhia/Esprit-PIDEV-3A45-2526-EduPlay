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
     * @return User[] Parents (type = 'parent')
     */
    public function findParents(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.type = :type')
            ->setParameter('type', 'parent')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
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

    /**
     * Récupère tous les parents (avec rôle ROLE_PARENT)
     *
     * @return User[]
     */
    public function findAllParents(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PARENT%')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les professeurs (avec rôle ROLE_TEACHER)
     *
     * @return User[]
     */
    public function findAllTeachers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TEACHER%')
            ->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
     * Récupère les parents avec pagination
     */
    public function findParentsPaginated(int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PARENT%')
            ->orderBy('u.firstName', 'ASC');

        $total = count($qb->getQuery()->getResult());

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => $qb->getQuery()->getResult(),
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Compte le nombre total de parents
     */
    public function countParents(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PARENT%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les parents qui ont des enfants inscrits à des cours
     */
    public function findParentsWithSubscriptions(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.kids', 'k')
            ->leftJoin('k.subscriptions', 's')
            ->where('u.roles LIKE :role')
            ->andWhere('s.id IS NOT NULL')
            ->setParameter('role', '%ROLE_PARENT%')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de parents par critères
     */
    public function searchParents(string $searchTerm = ''): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PARENT%');

        if (!empty($searchTerm)) {
            $qb->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $qb->orderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }


}