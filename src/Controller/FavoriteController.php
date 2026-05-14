<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Game;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class FavoriteController extends AbstractController
{
    #[Route('/game/{id}/favorite', name: 'game_favorite_toggle', methods: ['POST'])]
    public function toggle(
        Game $game,
        FavoriteRepository $repo,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new Response('', 403);
        }

        $favorite = $repo->findOneBy([
            'user' => $user,
            'game' => $game
        ]);

        if ($favorite) {
            $em->remove($favorite);
            $isFavorite = false;
        } else {
            $favorite = (new Favorite())
                ->setUser($user)
                ->setGame($game);

            $em->persist($favorite);
            $isFavorite = true;
        }

        $em->flush();

        return $this->render('FrontOffice/enfant/game/_favorite_button.html.twig', [
            'game' => $game,
            'isFavorite' => $isFavorite
        ]);
    }
}