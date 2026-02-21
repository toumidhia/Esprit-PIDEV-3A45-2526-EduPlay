<?php
// src/Repository/BookRequestRepository.php

namespace App\Repository;

use App\Entity\BookRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookRequest::class);
    }

    // Demandes non notifiées pour un titre donné
    public function findPendingByTitle(string $title): array
    {
        return $this->createQueryBuilder('b')
            ->where('LOWER(b.bookTitle) LIKE LOWER(:title)')
            ->andWhere('b.isNotified = false')
            ->setParameter('title', '%' . $title . '%')
            ->getQuery()
            ->getResult();
    }

    // Notifications pour un enfant connecté
    public function findNotificationsForEnfant(User $enfant): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.enfant = :enfant')
            ->andWhere('b.isAvailable = true')
            ->andWhere('b.isNotified = true')
            ->setParameter('enfant', $enfant)
            ->orderBy('b.notifiedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Toutes les demandes en attente (pour l'admin)
    public function findAllPending(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.isNotified = false')
            ->orderBy('b.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}