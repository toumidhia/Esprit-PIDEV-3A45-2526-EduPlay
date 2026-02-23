<?php

namespace App\RecommendationBundle\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;

class RecommendationProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private CommandeRepository $commandeRepository,
    ) {
    }

    /**
     * Recommandations intelligentes basées sur l'IA pour un utilisateur
     * @return Product[]
     */
    public function getRecommendationsForUser(?User $user = null, int $limit = 5): array
    {
        $recommendations = [];

        // 1. Si l'utilisateur a des commandes précédentes, recommander des produits similaires
        if ($user) {
            $userCommandes = $this->commandeRepository->findBy(
                ['user' => $user],
                ['dateCommande' => 'DESC'],
                10
            );

            if (!empty($userCommandes)) {
                $purchasedProductIds = array_map(fn($c) => $c->getProduct()->getId(), $userCommandes);
                $avgPrice = array_sum(array_map(fn($c) => $c->getProduct()->getPrice(), $userCommandes)) / count($userCommandes);
                
                // Recommander des produits similaires en prix (±30%)
                $similarProducts = $this->productRepository->createQueryBuilder('p')
                    ->where('p.availability = :true')
                    ->andWhere('p.id NOT IN (:excluded)')
                    ->andWhere('p.price BETWEEN :minPrice AND :maxPrice')
                    ->setParameter('true', true)
                    ->setParameter('excluded', $purchasedProductIds ?: [0])
                    ->setParameter('minPrice', $avgPrice * 0.7)
                    ->setParameter('maxPrice', $avgPrice * 1.3)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
                
                $recommendations = array_merge($recommendations, $similarProducts);
            }
        }

        // 2. Produits les plus populaires (les plus commandés)
        $popularProducts = $this->getPopularProducts($limit);
        foreach ($popularProducts as $product) {
            if (!in_array($product, $recommendations, true)) {
                $recommendations[] = $product;
            }
        }

        // 3. Si pas assez de recommandations, ajouter des produits récents disponibles
        if (count($recommendations) < $limit) {
            $recentProducts = $this->productRepository->findBy(
                ['availability' => true],
                ['id' => 'DESC'],
                $limit - count($recommendations)
            );
            foreach ($recentProducts as $product) {
                if (!in_array($product, $recommendations, true)) {
                    $recommendations[] = $product;
                }
            }
        }

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Produits fréquemment achetés ensemble (collaborative filtering)
     */
    public function getFrequentlyBoughtTogether(Product $product, int $limit = 3): array
    {
        // Trouver les commandes contenant ce produit
        $commandesWithProduct = $this->commandeRepository->createQueryBuilder('c')
            ->where('c.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getResult();

        if (empty($commandesWithProduct)) {
            return [];
        }

        $userIds = array_unique(array_map(fn($c) => $c->getUser()->getId(), $commandesWithProduct));
        
        // Trouver les autres produits commandés par ces mêmes utilisateurs
        $otherProductsData = $this->commandeRepository->createQueryBuilder('c')
            ->select('p.id', 'COUNT(c.id) as orderCount')
            ->join('c.product', 'p')
            ->where('c.user IN (:userIds)')
            ->andWhere('p.id != :productId')
            ->andWhere('p.availability = :true')
            ->setParameter('userIds', $userIds)
            ->setParameter('productId', $product->getId())
            ->setParameter('true', true)
            ->groupBy('p.id')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($otherProductsData)) {
            return [];
        }

        // Extract product IDs
        $productIds = array_column($otherProductsData, 'id');
        
        // Fetch full Product entities
        $products = $this->productRepository->findBy([
            'id' => $productIds,
            'availability' => true
        ]);
        
        // Create a map for easy lookup
        $productMap = [];
        foreach ($products as $p) {
            $productMap[$p->getId()] = $p;
        }
        
        // Return products in the same order as results
        $result = [];
        foreach ($otherProductsData as $data) {
            if (isset($productMap[$data['id']])) {
                $result[] = $productMap[$data['id']];
            }
        }
        
        return $result;
    }

    /**
     * Produits les plus populaires
     */
    private function getPopularProducts(int $limit): array
    {
        // Get popular product IDs with their order counts
        $popularData = $this->commandeRepository->createQueryBuilder('c')
            ->select('p.id', 'COUNT(c.id) as orderCount')
            ->join('c.product', 'p')
            ->where('p.availability = :true')
            ->setParameter('true', true)
            ->groupBy('p.id')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        if (empty($popularData)) {
            return [];
        }
        
        // Get product IDs from results
        $productIds = array_column($popularData, 'id');
        
        // Fetch full Product entities
        $products = $this->productRepository->findBy([
            'id' => $productIds,
            'availability' => true
        ]);
        
        // Sort products in the same order as popularity results
        $sortedProducts = [];
        $productMap = [];
        foreach ($products as $product) {
            $productMap[$product->getId()] = $product;
        }
        
        foreach ($popularData as $data) {
            if (isset($productMap[$data['id']])) {
                $sortedProducts[] = $productMap[$data['id']];
            }
        }
        
        return $sortedProducts;
    }

    /**
     * Recommandations basées sur le contenu (similarité de description)
     */
    public function getContentBasedRecommendations(Product $product, int $limit = 5): array
    {
        $productWords = $this->extractKeywords($product->getDescription() . ' ' . $product->getName());
        
        $allProducts = $this->productRepository->findBy(
            ['availability' => true],
            ['id' => 'DESC']
        );

        $scores = [];
        foreach ($allProducts as $p) {
            if ($p->getId() === $product->getId()) {
                continue;
            }
            
            $pWords = $this->extractKeywords($p->getDescription() . ' ' . $p->getName());
            $similarity = $this->calculateSimilarity($productWords, $pWords);
            
            if ($similarity > 0.1) { // Seuil minimum de similarité
                $scores[] = ['product' => $p, 'score' => $similarity];
            }
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return array_map(fn($item) => $item['product'], array_slice($scores, 0, $limit));
    }

    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $words = preg_split('/\s+/', $text);
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'pour', 'avec', 'sur', 'dans'];
        return array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
    }

    private function calculateSimilarity(array $words1, array $words2): float
    {
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
}