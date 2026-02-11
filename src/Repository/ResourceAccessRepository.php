<?php
// src/Repository/ResourceAccessRepository.php
namespace App\Repository;

use App\Entity\ResourceAccess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResourceAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResourceAccess::class);
    }
    
    public function getEngagementStats(): array
    {
        return [
            // ... autres statistiques ...
            
            // 2. Ressources les plus populaires (par ouverture)
            'most_viewed_resources' => $this->createQueryBuilder('ra')
                ->select('r.title, r.type, l.name as library, SUM(ra.openCount) as total_opens')
                ->join('ra.resourceId', 'r')
                ->join('r.libraryId', 'l')
                ->groupBy('r.id')
                ->orderBy('SUM(ra.openCount)', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult(),
                
            // 3. Bibliothèques les plus consultées (par ouverture de ressources)
            'most_accessed_libraries' => $this->createQueryBuilder('ra')
                ->select('l.name, l.theme, l.level, SUM(ra.openCount) as total_opens')
                ->join('ra.resourceId', 'r')
                ->join('r.libraryId', 'l')
                ->groupBy('l.id')
                ->orderBy('SUM(ra.openCount)', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult(),
                
            // 4. Ressources complétées (NOUVEAU)
            'completed_resources' => $this->createQueryBuilder('ra')
                ->select('r.title, r.type, l.name as library, COUNT(ra.id) as completion_count')
                ->join('ra.resourceId', 'r')
                ->join('r.libraryId', 'l')
                ->where('ra.isCompleted = true')
                ->groupBy('r.id')
                ->orderBy('COUNT(ra.id)', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult(),
                
            // 5. Taux de complétion par ressource
            'completion_rates' => $this->createQueryBuilder('ra')
                ->select('r.title, 
                         COUNT(CASE WHEN ra.isCompleted = true THEN 1 END) as completed,
                         COUNT(ra.id) as total_attempts')
                ->join('ra.resourceId', 'r')
                ->groupBy('r.id')
                ->having('COUNT(ra.id) >= 3') // Seulement les ressources avec au moins 3 tentatives
                ->orderBy('(COUNT(CASE WHEN ra.isCompleted = true THEN 1 END) / COUNT(ra.id))', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult(),
                
            // 6. Enfants avec le plus de ressources complétées
            'top_completers' => $this->createQueryBuilder('ra')
                ->select('u.username, 
                         COUNT(CASE WHEN ra.isCompleted = true THEN 1 END) as completed_count')
                ->join('ra.childId', 'u')
                ->groupBy('u.id')
                ->orderBy('COUNT(CASE WHEN ra.isCompleted = true THEN 1 END)', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult(),
        ];
    }
    
    public function getFavoriteResources(): array
    {
        return $this->createQueryBuilder('ra')
            ->select('r.title, r.type, l.name as library, COUNT(ra.id) as favorite_count')
            ->join('ra.resourceId', 'r')
            ->join('r.libraryId', 'l')
            ->where('ra.isFavorite = true')
            ->groupBy('r.id')
            ->orderBy('COUNT(ra.id)', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}