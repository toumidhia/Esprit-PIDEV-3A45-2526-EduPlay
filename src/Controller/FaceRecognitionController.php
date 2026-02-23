<?php
// src/Controller/FaceRecognitionController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class FaceRecognitionController extends AbstractController
{
    #[Route('/api/face/register', name: 'api_face_register', methods: ['POST'])]
    public function registerFace(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $currentUser
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $embedding = $data['embedding'] ?? null;

        if (!$userId || !$embedding) {
            return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérification des permissions :
        // - Un parent peut enregistrer le visage de son enfant
        // - Un utilisateur peut enregistrer son propre visage
        // - Un admin peut tout faire
        if (!$this->canRegisterFace($currentUser, $user)) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $user->setFacialEmbedding(json_encode($embedding));
        $em->flush();

        return $this->json([
            'success' => true, 
            'message' => 'Visage enregistré avec succès',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'type' => $user->getType() ?? 'unknown'
            ]
        ]);
    }

    #[Route('/api/face/login', name: 'api_face_login', methods: ['POST'])]
    public function loginByFace(
        Request $request,
        UserRepository $userRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $embedding = $data['embedding'] ?? null;

        if (!$embedding) {
            return $this->json(['error' => 'Aucune donnée faciale fournie'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer TOUS les utilisateurs avec un embedding facial
        $users = $userRepo->createQueryBuilder('u')
            ->where('u.facialEmbedding IS NOT NULL')
            ->getQuery()
            ->getResult();

        if (empty($users)) {
            return $this->json([
                'success' => false, 
                'message' => 'Aucun utilisateur enregistré'
            ], Response::HTTP_NOT_FOUND);
        }

        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;
        $matches = []; // Pour debug

        foreach ($users as $user) {
            $storedEmbedding = json_decode($user->getFacialEmbedding(), true);
            
            // Vérifier que l'embedding est valide
            if (!is_array($storedEmbedding) || count($storedEmbedding) !== 128) {
                continue;
            }
            
            $distance = $this->euclideanDistance($embedding, $storedEmbedding);
            
            // Stocker pour debug
            $matches[] = [
                'user' => $user->getUsername(),
                'type' => $user->getType(),
                'distance' => $distance,
                'confidence' => (1 - $distance) * 100
            ];

            // Seuil plus strict pour les parents/admins (sécurité)
            $threshold = $this->getThresholdForUserType($user);
            
            if ($distance < $bestDistance && $distance < $threshold) {
                $bestDistance = $distance;
                $bestMatch = $user;
            }
        }

        if ($bestMatch) {
            // Créer une session ou retourner un token JWT
            return $this->json([
                'success' => true,
                'userId' => $bestMatch->getId(),
                'username' => $bestMatch->getUsername(),
                'email' => $bestMatch->getEmail(),
                'type' => $bestMatch->getType() ?? 'unknown',
                'confidence' => round((1 - $bestDistance) * 100, 2),
                'debug' => $matches // À retirer en production
            ]);
        }

        return $this->json([
            'success' => false, 
            'message' => 'Visage non reconnu',
            'debug' => $matches // À retirer en production
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/api/face/remove', name: 'api_face_remove', methods: ['POST'])]
    public function removeFace(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $currentUser
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? $currentUser?->getId();

        if (!$userId) {
            return $this->json(['error' => 'ID utilisateur manquant'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepo->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les permissions
        if (!$this->canRemoveFace($currentUser, $user)) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $user->setFacialEmbedding(null);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Empreinte faciale supprimée'
        ]);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return PHP_FLOAT_MAX;
        }
        
        $sum = 0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += pow($a[$i] - $b[$i], 2);
        }
        return sqrt($sum);
    }

    private function getThresholdForUserType(User $user): float
    {
        $type = $user->getType() ?? 'enfant';
        
        // Seuils plus stricts pour les comptes sensibles
        return match($type) {
            'admin' => 0.4,  // Très strict
            'parent' => 0.5,  // Strict
            default => 0.6    // Plus tolérant pour les enfants
        };
    }

    private function canRegisterFace(?User $currentUser, User $targetUser): bool
    {
        // Non connecté
        if (!$currentUser) {
            return false;
        }

        // Admin peut tout faire
        if ($currentUser->getType() === 'admin') {
            return true;
        }

        // Un utilisateur peut enregistrer son propre visage
        if ($currentUser->getId() === $targetUser->getId()) {
            return true;
        }

        // Un parent peut enregistrer le visage de son enfant
        // À adapter selon votre relation parent-enfant
        if ($currentUser->getType() === 'parent' && $targetUser->getType() === 'enfant') {
            // Vérifier que c'est bien son enfant
            // return $currentUser->getEnfants()->contains($targetUser);
            return true; // À remplacer par votre logique
        }

        return false;
    }

    private function canRemoveFace(?User $currentUser, User $targetUser): bool
    {
        return $this->canRegisterFace($currentUser, $targetUser);
    }
}