<?php
// src/Repository/LibraryRepository.php

namespace App\Repository;

use App\Entity\Library;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LibraryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Library::class);
    }

    /**
     * Recherche et tri des bibliothèques
     */
    public function search(array $filters = [])
    {
        $qb = $this->createQueryBuilder('l');

        // 1. FILTRES DE RECHERCHE
        // Mot-clé (nom, description)
        if (!empty($filters['keyword'])) {
            $qb->andWhere('l.name LIKE :keyword OR l.description LIKE :keyword')
               ->setParameter('keyword', '%' . $filters['keyword'] . '%');
        }

        // Niveau
        if (!empty($filters['level'])) {
            $qb->andWhere('l.level = :level')
               ->setParameter('level', $filters['level']);
        }

        // Thème
        if (!empty($filters['theme'])) {
            $qb->andWhere('l.theme LIKE :theme')
               ->setParameter('theme', '%' . $filters['theme'] . '%');
        }

        // 2. TRI
        if (!empty($filters['sortBy'])) {
            switch ($filters['sortBy']) {
                // Nom
                case 'name_asc':
                    $qb->orderBy('l.name', 'ASC');
                    break;
                case 'name_desc':
                    $qb->orderBy('l.name', 'DESC');
                    break;
                
                // Thème
                case 'theme_asc':
                    $qb->orderBy('l.theme', 'ASC');
                    break;
                case 'theme_desc':
                    $qb->orderBy('l.theme', 'DESC');
                    break;
                
                // Niveau (ordre personnalisé)
                case 'level_asc':
                    $qb->orderBy("
                        CASE 
                            WHEN l.level = 'Débutant' THEN 1
                            WHEN l.level = 'Intermédiaire' THEN 2
                            WHEN l.level = 'Avancé' THEN 3
                            WHEN l.level = 'Expert' THEN 4
                            ELSE 5
                        END", 'ASC');
                    break;
                case 'level_desc':
                    $qb->orderBy("
                        CASE 
                            WHEN l.level = 'Débutant' THEN 1
                            WHEN l.level = 'Intermédiaire' THEN 2
                            WHEN l.level = 'Avancé' THEN 3
                            WHEN l.level = 'Expert' THEN 4
                            ELSE 5
                        END", 'DESC');
                    break;
                
                // Âge minimum
                case 'minAge_asc':
                    $qb->orderBy('l.minAge', 'ASC');
                    break;
                case 'minAge_desc':
                    $qb->orderBy('l.minAge', 'DESC');
                    break;
                
                // Âge maximum
                case 'maxAge_asc':
                    $qb->orderBy('l.maxAge', 'ASC');
                    break;
                case 'maxAge_desc':
                    $qb->orderBy('l.maxAge', 'DESC');
                    break;
                
                default:
                    $qb->orderBy('l.name', 'ASC');
            }
        } else {
            // Tri par défaut
            $qb->orderBy('l.name', 'ASC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les thèmes distincts (pour autocomplete)
     */
    public function findAllThemes(): array
    {
        return $this->createQueryBuilder('l')
            ->select('DISTINCT l.theme')
            ->orderBy('l.theme', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }
}