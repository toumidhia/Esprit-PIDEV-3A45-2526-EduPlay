<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Product;
use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;

class AnomalyDetectionService
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private ProductRepository $productRepository,
    ) {
    }

    /**
     * Détecte toutes les anomalies dans le système
     * @return array{commandes: array, produits: array, utilisateurs: array}
     */
    public function detectAllAnomalies(): array
    {
        return [
            'commandes' => $this->detectCommandeAnomalies(),
            'produits' => $this->detectProductAnomalies(),
            'utilisateurs' => $this->detectUserAnomalies(),
        ];
    }

    /**
     * Détecte les anomalies dans les commandes
     */
    public function detectCommandeAnomalies(): array
    {
        $anomalies = [];
        $allCommandes = $this->commandeRepository->findAll();

        if (empty($allCommandes)) {
            return [];
        }

        // Calculer les statistiques de base
        $amounts = array_map(fn($c) => $c->getTotalAmount(), $allCommandes);
        $quantities = array_map(fn($c) => $c->getQuantity(), $allCommandes);
        
        $avgAmount = array_sum($amounts) / count($amounts);
        $avgQuantity = array_sum($quantities) / count($quantities);
        
        $stdDevAmount = $this->calculateStandardDeviation($amounts);
        $stdDevQuantity = $this->calculateStandardDeviation($quantities);

        foreach ($allCommandes as $commande) {
            $issues = [];

            // 1. Montant anormalement élevé (> moyenne + 2 écarts-types)
            if ($commande->getTotalAmount() > $avgAmount + (2 * $stdDevAmount)) {
                $issues[] = [
                    'type' => 'montant_eleve',
                    'severity' => 'high',
                    'message' => sprintf(
                        'Montant anormalement élevé : %d € (moyenne : %.2f €)',
                        $commande->getTotalAmount(),
                        $avgAmount
                    ),
                ];
            }

            // 2. Quantité anormalement élevée
            if ($commande->getQuantity() > $avgQuantity + (2 * $stdDevQuantity)) {
                $issues[] = [
                    'type' => 'quantite_elevee',
                    'severity' => 'medium',
                    'message' => sprintf(
                        'Quantité anormalement élevée : %d (moyenne : %.2f)',
                        $commande->getQuantity(),
                        $avgQuantity
                    ),
                ];
            }

            // 3. Incohérence prix × quantité ≠ montant total
            $expectedAmount = (int) round($commande->getIdProduct()->getPrice() * $commande->getQuantity());
            $difference = abs($commande->getTotalAmount() - $expectedAmount);
            if ($difference > 1) { // Tolérance de 1€
                $issues[] = [
                    'type' => 'incoherence_calcul',
                    'severity' => 'high',
                    'message' => sprintf(
                        'Incohérence de calcul : attendu %d €, obtenu %d € (différence : %d €)',
                        $expectedAmount,
                        $commande->getTotalAmount(),
                        $difference
                    ),
                ];
            }

            if (!empty($issues)) {
                $anomalies[] = [
                    'commande' => $commande,
                    'issues' => $issues,
                ];
            }
        }

        // 4. Détecter les commandes multiples rapides du même utilisateur
        $rapidOrders = $this->detectRapidMultipleOrders();
        $anomalies = array_merge($anomalies, $rapidOrders);

        return $anomalies;
    }

    /**
     * Détecte les anomalies dans les produits
     */
    public function detectProductAnomalies(): array
    {
        $anomalies = [];
        $allProducts = $this->productRepository->findAll();

        if (empty($allProducts)) {
            return [];
        }

        $prices = array_map(fn($p) => $p->getPrice(), $allProducts);
        $avgPrice = array_sum($prices) / count($prices);
        $stdDevPrice = $this->calculateStandardDeviation($prices);

        foreach ($allProducts as $product) {
            $issues = [];

            // Prix anormalement élevé ou bas
            if ($product->getPrice() > $avgPrice + (2 * $stdDevPrice)) {
                $issues[] = [
                    'type' => 'prix_eleve',
                    'severity' => 'medium',
                    'message' => sprintf(
                        'Prix anormalement élevé : %.2f € (moyenne : %.2f €)',
                        $product->getPrice(),
                        $avgPrice
                    ),
                ];
            } elseif ($product->getPrice() < $avgPrice - (2 * $stdDevPrice) && $product->getPrice() > 0) {
                $issues[] = [
                    'type' => 'prix_bas',
                    'severity' => 'low',
                    'message' => sprintf(
                        'Prix anormalement bas : %.2f € (moyenne : %.2f €)',
                        $product->getPrice(),
                        $avgPrice
                    ),
                ];
            }

            // Produit disponible mais jamais commandé (après un certain temps)
            $commandeCount = $this->commandeRepository->count(['idProduct' => $product]);
            if ($product->isAvailability() && $commandeCount === 0) {
                $issues[] = [
                    'type' => 'produit_non_vendu',
                    'severity' => 'low',
                    'message' => 'Produit disponible mais jamais commandé',
                ];
            }

            if (!empty($issues)) {
                $anomalies[] = [
                    'product' => $product,
                    'issues' => $issues,
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Détecte les anomalies liées aux utilisateurs
     */
    public function detectUserAnomalies(): array
    {
        $anomalies = [];
        
        // Commandes multiples rapides du même utilisateur
        $rapidOrders = $this->detectRapidMultipleOrders();
        
        foreach ($rapidOrders as $anomaly) {
            $userId = $anomaly['commande']->getIdUser()->getId();
            if (!isset($anomalies[$userId])) {
                $anomalies[$userId] = [
                    'user' => $anomaly['commande']->getIdUser(),
                    'issues' => [],
                ];
            }
            $anomalies[$userId]['issues'][] = $anomaly['issues'][0];
        }

        return array_values($anomalies);
    }

    /**
     * Détecte les commandes multiples rapides (potentiel spam/fraude)
     */
    private function detectRapidMultipleOrders(): array
    {
        $anomalies = [];
        $allCommandes = $this->commandeRepository->findBy([], ['dateCommande' => 'ASC']);

        $userOrders = [];
        foreach ($allCommandes as $commande) {
            $userId = $commande->getIdUser()->getId();
            if (!isset($userOrders[$userId])) {
                $userOrders[$userId] = [];
            }
            $userOrders[$userId][] = $commande;
        }

        foreach ($userOrders as $userId => $orders) {
            if (count($orders) < 2) {
                continue;
            }

            for ($i = 1; $i < count($orders); $i++) {
                $timeDiff = $orders[$i]->getDateCommande()->getTimestamp() - $orders[$i-1]->getDateCommande()->getTimestamp();
                
                // Si deux commandes dans les 5 minutes
                if ($timeDiff < 300) {
                    $anomalies[] = [
                        'commande' => $orders[$i],
                        'issues' => [[
                            'type' => 'commande_rapide',
                            'severity' => 'medium',
                            'message' => sprintf(
                                'Commande multiple rapide : %d commandes en moins de 5 minutes',
                                count($orders)
                            ),
                        ]],
                    ];
                    break;
                }
            }
        }

        return $anomalies;
    }

    /**
     * Calcule l'écart-type
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);
        
        return sqrt($variance);
    }
}
