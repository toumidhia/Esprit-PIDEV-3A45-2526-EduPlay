<?php
// src/Repository/ResourceRepository.php

namespace App\Repository;

use App\Entity\Resource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resource>
 */
class ResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resource::class);
    }

    /**
     * Recherche et tri des ressources
     */
    public function searchAndSort(array $criteria = []): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->leftJoin('r.libraryId', 'l')
            ->addSelect('l');

        // Recherche globale (dans titre, auteur, résumé)
        if (!empty($criteria['keyword'])) {
            $keyword = $criteria['keyword'];
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('r.title', ':keyword'),
                    $queryBuilder->expr()->like('r.author', ':keyword'),
                    $queryBuilder->expr()->like('r.summary', ':keyword')
                )
            )
            ->setParameter('keyword', '%' . $keyword . '%');
        }

        // Recherche spécifique par titre
        if (!empty($criteria['title'])) {
            $queryBuilder->andWhere('r.title LIKE :title')
                ->setParameter('title', '%' . $criteria['title'] . '%');
        }

        // Recherche spécifique par auteur
        if (!empty($criteria['author'])) {
            $queryBuilder->andWhere('r.author LIKE :author')
                ->setParameter('author', '%' . $criteria['author'] . '%');
        }

        // Recherche par mot-clé dans le résumé
        if (!empty($criteria['summaryKeyword'])) {
            $queryBuilder->andWhere('r.summary LIKE :summaryKeyword')
                ->setParameter('summaryKeyword', '%' . $criteria['summaryKeyword'] . '%');
        }

        // Tri
        if (!empty($criteria['sortBy'])) {
            switch ($criteria['sortBy']) {
                case 'title_asc':
                    $queryBuilder->orderBy('r.title', 'ASC');
                    break;
                case 'title_desc':
                    $queryBuilder->orderBy('r.title', 'DESC');
                    break;
                case 'author_asc':
                    $queryBuilder->orderBy('r.author', 'ASC');
                    break;
                case 'author_desc':
                    $queryBuilder->orderBy('r.author', 'DESC');
                    break;
                case 'minAge_asc':
                    $queryBuilder->orderBy('r.minAge', 'ASC');
                    break;
                case 'minAge_desc':
                    $queryBuilder->orderBy('r.minAge', 'DESC');
                    break;
                case 'maxAge_asc':
                    $queryBuilder->orderBy('r.maxAge', 'ASC');
                    break;
                case 'maxAge_desc':
                    $queryBuilder->orderBy('r.maxAge', 'DESC');
                    break;
                default:
                    $queryBuilder->orderBy('r.id', 'DESC');
                    break;
            }
        } else {
            $queryBuilder->orderBy('r.id', 'DESC');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}