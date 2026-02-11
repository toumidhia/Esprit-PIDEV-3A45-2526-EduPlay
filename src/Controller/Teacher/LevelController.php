<?php

namespace App\Controller\Teacher;
use Symfony\Component\Form\FormError;


use App\Entity\Level;
use App\Form\LevelType;
use App\Repository\LevelRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;


#[Route('/enseignant/level')]
final class LevelController extends AbstractController
{
    #[Route(name: 'teacher_level_index', methods: ['GET'])]
    public function index(Request $request, LevelRepository $levelRepository): Response
    {
        // Filters
        $filters = [
            'search' => $request->query->get('search', ''),
            'difficulty' => $request->query->get('difficulty', ''), // si tu as difficulty
        ];

        // Sort
        $sortBy = $request->query->get('sort', 'id');      // id, name, difficulty...
        $sortOrder = $request->query->get('order', 'DESC');

        // Data
        $levels = $levelRepository->findWithFilters($filters, $sortBy, $sortOrder);

        // Statistics (optionnel)
        $statistics = $levelRepository->getLevelStatistics();
       

        // AJAX: renvoyer juste le grid
        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/teacher/level/_grid.html.twig', [
                'levels' => $levels,
            ]);
        }

        // Normal
        return $this->render('BackOffice/teacher/level/index.html.twig', [
            'levels' => $levels,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'statistics' => $statistics,
        ]);
    }

  #[Route('/new', name: 'teacher_level_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $level = new Level();
    $form = $this->createForm(LevelType::class, $level);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

        $minAge = $form->get('minAge')->getData();
        $maxAge = $form->get('maxAge')->getData();

        // ✅ Vérifier que maxAge > minAge
        if ($minAge !== null && $maxAge !== null && $maxAge <= $minAge) {
            // erreur globale (va apparaître avec form_errors(form))
            $form->addError(new FormError("L’âge maximum doit être supérieur à l’âge minimum."));
        }

        if ($form->isValid()) {
            $now = new \DateTimeImmutable();
            $level->setCreatedAt($now);
            $level->setUpdatedAt($now);

            $em->persist($level);
            $em->flush();

            $this->addFlash('success', 'Level created successfully!');
            return $this->redirectToRoute('teacher_level_index');
        }
    }

    return $this->render('BackOffice/teacher/level/new.html.twig', [
        'level' => $level,
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'teacher_level_show', methods: ['GET'])]
    public function show(Level $level,GameRepository $gameRepository,): Response
    {
        // Liste des games qui appartiennent à ce level
    $games = $gameRepository->findBy(['idLevel' => $level]);

    // Nombre de games
    $gamesCount = count($games);
        return $this->render('BackOffice/teacher/level/show.html.twig', [
            'level' => $level,
            'games' => $games,
        'gamesCount' => $gamesCount,
        ]);
    }

    

#[Route('/{id}/edit', name: 'teacher_level_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Level $level, EntityManagerInterface $em): Response
{
    $form = $this->createForm(LevelType::class, $level);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        $minAge = $form->get('minAge')->getData();
        $maxAge = $form->get('maxAge')->getData();

        // ✅ Vérifier que maxAge > minAge
        if ($minAge !== null && $maxAge !== null && $maxAge <= $minAge) {
            // erreur globale (affichée avec form_errors(form))
            $form->addError(new FormError("L’âge maximum doit être supérieur à l’âge minimum."));
        }

        if ($form->isValid()) {
            $now = new \DateTimeImmutable();
            $level->setUpdatedAt($now);

            $em->flush();

            $this->addFlash('success', 'Level updated successfully!');
            return $this->redirectToRoute('teacher_level_index');
        }
    }

    return $this->render('BackOffice/teacher/level/edit.html.twig', [
        'level' => $level,
        'form' => $form,
    ]);
}


 

#[Route('/{id}', name: 'teacher_level_delete', methods: ['POST'])]
public function delete(
    Request $request,
    Level $level,
    EntityManagerInterface $em,
    GameRepository $gameRepository
): Response {
    if (!$this->isCsrfTokenValid('delete'.$level->getId(), $request->getPayload()->getString('_token'))) {
        return $this->redirectToRoute('teacher_level_index');
    }

    // ✅ Vérifier si le level est utilisé par au moins un game
    $usedCount = $gameRepository->count(['idLevel' => $level]);

    if ($usedCount > 0) {
        $this->addFlash('error', "Impossible de supprimer ce niveau : il est utilisé par $usedCount jeu.");
        return $this->redirectToRoute('teacher_level_index');
    }

    $em->remove($level);
    $em->flush();

    $this->addFlash('success', 'Level deleted successfully!');
    return $this->redirectToRoute('teacher_level_index');
}

}