<?php

namespace App\AnomalyBundle\Service;

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
     * @return array{suspicious_orders: array, summary: array}
     */
    public function detectAllAnomalies(): array
    {
        $suspiciousOrders = $this->detectSuspiciousOrders();
        
        return [
            'suspicious_orders' => $suspiciousOrders,
            'summary' => $this->generateSummary($suspiciousOrders),
        ];
    }

    /**
     * Détecte les commandes suspectes (fraude/anomalies)
     */
    public function detectSuspiciousOrders(): array
    {
        $suspiciousOrders = [];
        $allCommandes = $this->commandeRepository->findAll();

        foreach ($allCommandes as $commande) {
            $flags = [];

            // 1. Détecte les commandes rapides du même utilisateur
            if ($this->isRapidOrder($commande)) {
                $flags[] = [
                    'type' => 'rapid_orders',
                    'severity' => 'high',
                    'title' => 'Commandes multiples rapides',
                    'description' => 'Cet utilisateur a passé plusieurs commandes en très peu de temps',
                ];
            }

            // 2. Détecte les quantités anormalement élevées
            if ($this->hasAbnormalQuantity($commande)) {
                $flags[] = [
                    'type' => 'high_quantity',
                    'severity' => 'medium',
                    'title' => 'Quantité anormalement élevée',
                    'description' => sprintf(
                        'Quantité : %d unités (max habituel : ~50)',
                        $commande->getQuantity()
                    ),
                ];
            }

            // 3. Détecte les montants anormalement élevés
            if ($this->hasAbnormalAmount($commande)) {
                $flags[] = [
                    'type' => 'high_amount',
                    'severity' => 'high',
                    'title' => 'Montant anormalement élevé',
                    'description' => sprintf(
                        'Montant : %.2f € (montant moyen : ~%.2f €)',
                        $commande->getTotalAmount(),
                        $this->getAverageOrderAmount()
                    ),
                ];
            }

            // 4. Détecte les paiements échoués multiples
            if ($this->hasMultipleFailedPayments($commande)) {
                $flags[] = [
                    'type' => 'multiple_failed_payments',
                    'severity' => 'high',
                    'title' => 'Paiements échoués multiples',
                    'description' => 'Cet utilisateur a eu plusieurs tentatives de paiement échouées',
                ];
            }

            // 5. Détecte les incohérences de calcul
            if ($this->hasCalculationInconsistency($commande)) {
                $flags[] = [
                    'type' => 'calculation_error',
                    'severity' => 'high',
                    'title' => 'Incohérence de calcul détectée',
                    'description' => 'Le prix total ne correspond pas au prix × quantité',
                ];
            }

            if (!empty($flags)) {
                $suspiciousOrders[] = [
                    'commande' => $commande,
                    'flags' => $flags,
                    'total_flags' => count($flags),
                    'max_severity' => $this->getMaxSeverity($flags),
                ];
            }
        }

        // Trier par nombre de flags et sévérité
        usort($suspiciousOrders, fn($a, $b) => 
            $b['total_flags'] <=> $a['total_flags'] ?: 
            $this->compareSeverity($b['max_severity'], $a['max_severity'])
        );

        return $suspiciousOrders;
    }

    /**
     * Vérifie si une commande est une commande rapide (peu de temps après une autre du même user)
     */
    private function isRapidOrder(Commande $commande): bool
    {
        $userOrders = $this->commandeRepository->findBy(
            ['user' => $commande->getUser()],
            ['dateCommande' => 'ASC']
        );

        if (count($userOrders) < 2) {
            return false;
        }

        // Vérifier si deux commandes sont à moins de 2 minutes d'intervalle
        foreach ($userOrders as $order) {
            if ($order->getId() === $commande->getId()) {
                continue;
            }
            
            $timeDiff = abs($order->getDateCommande()->getTimestamp() - $commande->getDateCommande()->getTimestamp());
            if ($timeDiff > 0 && $timeDiff < 120) { // Moins de 2 minutes
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte les quantités anormales
     */
    private function hasAbnormalQuantity(Commande $commande): bool
    {
        $allCommandes = $this->commandeRepository->findAll();
        
        if (empty($allCommandes)) {
            return false;
        }

        $quantities = array_map(fn($c) => $c->getQuantity(), $allCommandes);
        $avgQuantity = array_sum($quantities) / count($quantities);
        $stdDev = $this->calculateStandardDeviation($quantities);

        // Flaguer si > moyenne + 2 écarts-types OU > 50 unités
        return $commande->getQuantity() > max($avgQuantity + (2 * $stdDev), 50);
    }

    /**
     * Détecte les montants anormaux
     */
    private function hasAbnormalAmount(Commande $commande): bool
    {
        $allCommandes = $this->commandeRepository->findAll();
        
        if (empty($allCommandes)) {
            return false;
        }

        $amounts = array_map(fn($c) => $c->getTotalAmount(), $allCommandes);
        $avgAmount = array_sum($amounts) / count($amounts);
        $stdDev = $this->calculateStandardDeviation($amounts);

        // Flaguer si > moyenne + 2 écarts-types
        return $commande->getTotalAmount() > ($avgAmount + (2 * $stdDev));
    }

    /**
     * Obtient le montant moyen des commandes
     */
    private function getAverageOrderAmount(): float
    {
        $allCommandes = $this->commandeRepository->findAll();
        
        if (empty($allCommandes)) {
            return 0;
        }

        $amounts = array_map(fn($c) => $c->getTotalAmount(), $allCommandes);
        return array_sum($amounts) / count($amounts);
    }

    /**
     * Détecte si l'utilisateur a eu plusieurs paiements échoués
     */
    private function hasMultipleFailedPayments(Commande $commande): bool
    {
        // Compter les commandes non payées du même utilisateur
        $userOrders = $this->commandeRepository->findBy([
            'user' => $commande->getUser(),
            'isPaid' => false,
        ]);

        // Flaguer si plus de 2 paiements échoués
        return count($userOrders) >= 2;
    }

    /**
     * Détecte les incohérences de calcul
     */
    private function hasCalculationInconsistency(Commande $commande): bool
    {
        $expectedAmount = (int) round($commande->getProduct()->getPrice() * $commande->getQuantity());
        $difference = abs($commande->getTotalAmount() - $expectedAmount);
        
        return $difference > 1; // Tolérance de 1€
    }

    /**
     * Obtient la sévérité maximale d'une liste de flags
     */
    private function getMaxSeverity(array $flags): string
    {
        $severities = ['high' => 3, 'medium' => 2, 'low' => 1];
        
        $maxLevel = 0;
        $maxSeverity = 'low';
        
        foreach ($flags as $flag) {
            $level = $severities[$flag['severity']] ?? 0;
            if ($level > $maxLevel) {
                $maxLevel = $level;
                $maxSeverity = $flag['severity'];
            }
        }
        
        return $maxSeverity;
    }

    /**
     * Compare deux sévérités
     */
    private function compareSeverity(string $severity1, string $severity2): int
    {
        $severities = ['high' => 3, 'medium' => 2, 'low' => 1];
        return $severities[$severity1] ?? 0 <=> $severities[$severity2] ?? 0;
    }

    /**
     * Génère un résumé des anomalies détectées
     */
    private function generateSummary(array $suspiciousOrders): array
    {
        $summary = [
            'total_suspicious' => count($suspiciousOrders),
            'high_severity' => 0,
            'medium_severity' => 0,
            'low_severity' => 0,
            'by_type' => [],
        ];

        foreach ($suspiciousOrders as $order) {
            $summary[$order['max_severity'] . '_severity']++;
            
            foreach ($order['flags'] as $flag) {
                $type = $flag['type'];
                if (!isset($summary['by_type'][$type])) {
                    $summary['by_type'][$type] = 0;
                }
                $summary['by_type'][$type]++;
            }
        }

        return $summary;
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
