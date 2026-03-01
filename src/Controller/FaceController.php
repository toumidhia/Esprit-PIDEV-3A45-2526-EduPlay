<?php
// src/Controller/FaceController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FaceController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/face/register', name: 'app_face_register')]
    public function register(#[CurrentUser] ?User $user): Response
    {
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('FrontOffice/security/register_face.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/face/register/{id}', name: 'app_face_register_for_user')]
    public function registerForUser(
        User $targetUser,
        #[CurrentUser] ?User $currentUser
    ): Response {
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('edit', $targetUser)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('FrontOffice/security/register_face.html.twig', [
            'user' => $targetUser
        ]);
    }

    #[Route('/face/login', name: 'app_face_login')]
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('FrontOffice/security/login_face.html.twig');
    }

    #[Route('/api/face/login', name: 'api_face_login', methods: ['POST'])]
    public function loginByFace(
        Request $request,
        UserRepository $userRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['embedding']) || !is_array($data['embedding'])) {
            return $this->json(['error' => 'Aucune donnée faciale fournie'], Response::HTTP_BAD_REQUEST);
        }

        /** @var list<float> $embedding */
        $embedding = $data['embedding'];

        if (count($embedding) !== 128) {
            return $this->json(['error' => 'Embedding invalide'], Response::HTTP_BAD_REQUEST);
        }

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
        $matches = [];

        foreach ($users as $user) {
            $storedEmbeddingRaw = $user->getFacialEmbedding();
            if ($storedEmbeddingRaw === null) {
                continue;
            }

            $storedEmbedding = json_decode($storedEmbeddingRaw, true);

            if (!is_array($storedEmbedding) || count($storedEmbedding) !== 128) {
                continue;
            }

            /** @var list<float> $storedEmbedding */
            $distance = $this->euclideanDistance($embedding, $storedEmbedding);

            $matches[] = [
                'user' => $user->getUsername() ?? 'Sans Identifiant',
                'type' => $user->getType(),
                'distance' => $distance,
                'confidence' => round((1 - $distance) * 100, 2)
            ];

            $threshold = $this->getThresholdForUserType($user);

            if ($distance < $bestDistance && $distance < $threshold) {
                $bestDistance = $distance;
                $bestMatch = $user;
            }
        }

        if ($bestMatch) {
            if (!$bestMatch->isActive()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Compte non activé. Veuillez vérifier votre email.'
                ], Response::HTTP_FORBIDDEN);
            }

            return $this->json([
                'success' => true,
                'userId' => $bestMatch->getId(),
                'username' => $bestMatch->getUsername() ?? 'Utilisateur',
                'email' => $bestMatch->getEmail() ?? '',
                'type' => $bestMatch->getType(),
                'firstName' => $bestMatch->getFirstName() ?? '',
                'confidence' => round((1 - $bestDistance) * 100, 2),
                'message' => 'Visage reconnu avec succès'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => 'Visage non reconnu',
            'matches' => $matches
        ], Response::HTTP_NOT_FOUND);
    }

    #[Route('/login/face/confirm/{id}', name: 'app_face_login_confirm')]
    public function confirmLogin(
        User $user,
        Request $request,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->container->get('security.token_storage')->setToken($token);

        $session = $request->getSession();
        $session->set('_security_main', serialize($token));

        $event = new InteractiveLoginEvent($request, $token);
        $eventDispatcher->dispatch($event);

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->redirectToRoute('app_dashboard');
        }

        if (in_array('ROLE_PARENT', $roles, true)) {
            return $this->redirectToRoute('app_parent_dashboard');
        }

        if (in_array('ROLE_ENSEIGNANT', $roles, true)) {
            return $this->redirectToRoute('teacher_game_index');
        }

        if (in_array('ROLE_ENFANT', $roles, true)) {
            return $this->redirectToRoute('front_games');
        }

        return $this->redirectToRoute('app_course_index');
    }

    #[Route('/face/remove', name: 'app_face_remove', methods: ['POST'])]
    public function removeFace(
        Request $request,
        #[CurrentUser] ?User $currentUser
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? $currentUser?->getId();

        if (!$userId || !$currentUser) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($currentUser !== $user && !in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            return $this->json(['error' => 'Non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $user->setFacialEmbedding(null);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Empreinte faciale supprimée'
        ]);
    }

    /**
     * @param list<float> $a
     * @param list<float> $b
     */
    private function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return PHP_FLOAT_MAX;
        }

        $sum = 0.0;

        for ($i = 0, $len = count($a); $i < $len; $i++) {
            $sum += ($a[$i] - $b[$i]) ** 2;
        }

        return sqrt($sum);
    }

    private function getThresholdForUserType(User $user): float
    {
        $type = $user->getType();

        return match ($type) {
            'admin' => 0.4,
            'parent' => 0.5,
            default => 0.6,
        };
    }
}