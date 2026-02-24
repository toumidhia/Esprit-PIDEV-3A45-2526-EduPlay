<?php

namespace App\Controller;

use App\Entity\Game;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Service\GameNotificationMailer;
use App\Service\AiGameSummaryService;
use App\Repository\LevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\FavoriteRepository;




#[Route('/teacher/game')]
final class GameController extends AbstractController
{
    #[Route(name: 'teacher_game_index', methods: ['GET'])]
    public function index(
        Request $request,
        GameRepository $gameRepository,
        LevelRepository $levelRepository
    ): Response {
        // Filters (comme CourseController)
        $filters = [
            'search' => $request->query->get('search', ''),
            'type'   => $request->query->get('type', ''),
            'level'  => $request->query->get('level', ''), // level id
        ];
        $statistics = $gameRepository->getGameStatistics();

        // Sort
        $sortBy = $request->query->get('sort', 'id');     // id, name, type
        $sortOrder = $request->query->get('order', 'DESC');

        $games = $gameRepository->findWithFilters($filters, $sortBy, $sortOrder);
        $levels = $levelRepository->findBy([], ['name' => 'ASC']);

        if ($request->isXmlHttpRequest()) {
    return $this->render('BackOffice/enseignant/game/_grid.html.twig', [
        'games' => $games,
    ]);
}

        return $this->render('BackOffice/enseignant/game/index.html.twig', [
            'games' => $games,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'levels' => $levels,
            'statistics' => $statistics,
        ]);
    }

  





#[Route('/new', name: 'teacher_game_new', methods: ['GET','POST'])]
public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger,GameNotificationMailer $gameMailer,GameRepository $gameRepository): Response
{
    $game = new Game();

    // 1) si POST et image uploadée => move vers tmp et injecter imageTemp
    if ($request->isMethod('POST')) {
        $postData = $request->request->all('game');
        $fileData = $request->files->all('game');

        /** @var UploadedFile|null $imageFile */
        $imageFile = $fileData['image'] ?? null;

        if ($imageFile instanceof UploadedFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $tempFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            $imageFile->move($this->getParameter('upload_tmp_directory_games'), $tempFilename);

            $postData['imageTemp'] = $tempFilename;
            $request->request->set('game', $postData);

            // optionnel: éviter double traitement
            $fileData['image'] = null;
            $request->files->set('game', $fileData);
        }
    }

    // 2) détecter si on a une imageTemp pour désactiver NotBlank côté form
    $postedGame = $request->request->all('game');
    $hasTemp = !empty($postedGame['imageTemp'] ?? null);

    // 3) créer le form avec l'option has_temp
    $form = $this->createForm(GameType::class, $game, [
        'has_temp' => $hasTemp,
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $tempFilename = $form->get('imageTemp')->getData();

        // ✅ si l'utilisateur n’a pas re-upload -> on prend temp
        if ($tempFilename) {
            $tmpPath   = $this->getParameter('upload_tmp_directory_games').'/'.$tempFilename;
            $finalPath = $this->getParameter('upload_directory').'/'.$tempFilename;

            if (file_exists($tmpPath)) {
                @rename($tmpPath, $finalPath);
                $game->setImage($tempFilename);
            }
        }

        $em->persist($game);
        $em->flush();

        $childEmails = $gameRepository->findChildEmails();
    $gameMailer->sendNewGameToChildren($childEmails, $game);
    

        $this->addFlash('success', 'Game created successfully!');
        return $this->redirectToRoute('teacher_game_index');
    }

    return $this->render('BackOffice/enseignant/game/new.html.twig', [
        'game' => $game,
        'form' => $form,
    ]);
}





    

    #[Route('/{id}/edit', name: 'teacher_game_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Game $game, EntityManagerInterface $em, SluggerInterface $slugger): Response
{
    $oldImage = $game->getImage();

    $form = $this->createForm(GameType::class, $game, ['is_edit' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        /** @var UploadedFile|null $imageFile */
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            $imageFile->move($this->getParameter('upload_directory'), $newFilename);
            $game->setImage($newFilename);

            // supprimer l'ancienne image (si existe)
            if ($oldImage) {
                $oldPath = $this->getParameter('upload_directory').'/'.$oldImage;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
        } else {
            // si pas de nouvelle image uploadée, garder l'ancienne
            $game->setImage($oldImage);
        }

        $em->flush();
        $this->addFlash('success', 'Game updated successfully!');
        return $this->redirectToRoute('teacher_game_index');
    }

    return $this->render('BackOffice/enseignant/game/edit.html.twig', [
        'game' => $game,
        'form' => $form,
    ]);
}


    #[Route('/{id}', name: 'teacher_game_delete', methods: ['POST'])]
    public function delete(Request $request, Game $game, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$game->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($game);
            $em->flush();

            $this->addFlash('success', 'Game deleted successfully!');
        }

        return $this->redirectToRoute('teacher_game_index', [], Response::HTTP_SEE_OTHER);
    }
     






    #[Route('/teacher/dashboard', name: 'teacher_dashboard')]
    public function indexxx(): Response
    {
       
        return $this->render('BackOffice/enseignant/partials/base_admin.html.twig');
    }



#[Route('/games', name: 'front_games', methods: ['GET'])]
public function frontIndex(
    Request $request,
    GameRepository $gameRepository,
    LevelRepository $levelRepository,
    PaginatorInterface $paginator,FavoriteRepository $favoriteRepository
): Response {
   $filters = [
    'search' => $request->query->get('search', ''),
    'type' => $request->query->get('type', ''),
    'difficulty' => $request->query->get('difficulty', ''),
    'favoritesOnly' => $request->query->get('favoritesOnly', ''), // ✅ AJOUTER ICI
];

    $sortBy = $request->query->get('sort', 'id');
    $sortOrder = $request->query->get('order', 'DESC');

   $user = $this->getUser(); // ✅ AJOUTER CETTE LIGNE

$birthDate = $user?->getBirthDate();
$age = $birthDate ? $birthDate->diff(new \DateTimeImmutable())->y : null;

    // ✅ on récupère QueryBuilder
    $qb = $gameRepository->findPlayableForAgeQB($age, $filters, $sortBy, $sortOrder, $user);

    // ✅ pagination
    $games = $paginator->paginate(
        $qb,
        $request->query->getInt('page', 1),
        6 // nombre de jeux par page
    );
    $favoriteIds = [];
if ($this->getUser()) {
    $favorites = $favoriteRepository->findBy(['user' => $this->getUser()]);
    $favoriteIds = array_map(fn($f) => $f->getGame()->getId(), $favorites);
}

    if ($request->isXmlHttpRequest()) {
        return $this->render('FrontOffice/enfant/game/_grid.html.twig', [
            'games' => $games,
            'favoriteIds' => $favoriteIds,
        ]);
    }

    return $this->render('FrontOffice/enfant/game/index.html.twig', [
        'titre' => 'Games',
        'description' => 'Choisis un jeu et commence à jouer',
        'games' => $games,
        'filters' => $filters,
        'sortBy' => $sortBy,
        'sortOrder' => $sortOrder,
        'favoriteIds' => $favoriteIds,
    ]);
}


#[Route('/game/{id}', name: 'front_game_show_front', methods: ['GET'])]
public function frontShow(Game $game, AiGameSummaryService $ai): Response
{
    $birthDate = $this->getUser()->getBirthDate();
    $age = $birthDate ? $birthDate->diff(new \DateTimeImmutable())->y : null;

    // garde-fou: si age inconnu, on affiche la description normale
    $aiSummary = null;

    if ($age !== null) {
        $aiSummary = $ai->summarizeForAge((string) $game->getDescription(), $age);
        
    }

    return $this->render('FrontOffice/enfant/game/show.html.twig', [
        'game' => $game,
        'aiSummary' => $aiSummary,
        'age' => $age,
    ]);
}


    #[Route('/{id}', name: 'teacher_game_show', methods: ['GET'])]
    public function show(Game $game): Response
    {
        return $this->render('BackOffice/enseignant/game/show.html.twig', [
            'game' => $game,
        ]);
    }







    




}