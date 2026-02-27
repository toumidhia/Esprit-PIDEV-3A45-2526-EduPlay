<?php

namespace App\Controller;

use App\Entity\Seance;
use App\Form\SeanceType;
use App\Repository\SeanceRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/seance')]
final class SeanceController extends AbstractController
{
    // Admin only: View all sessions
    #[Route(name: 'app_seance_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request, SeanceRepository $seanceRepository, CourseRepository $courseRepository): Response
    {
        $user = $this->getUser();

        // Check if calendar view is requested
        $view = $request->query->get('view', 'list');

        // Get filter parameters
        $filters = [
            'search' => $request->query->get('search', ''),
            'courseId' => $request->query->get('course', ''),
            'startDate' => $request->query->get('startDate', ''),
            'endDate' => $request->query->get('endDate', ''),
            'month' => $request->query->get('month', ''),
        ];

        // Get sort parameters
        $sortBy = $request->query->get('sort', 'startTime');
        $sortOrder = $request->query->get('order', 'DESC');

        // Get filtered seances
        $seances = $seanceRepository->findWithFilters($filters, $sortBy, $sortOrder);

        // Get statistics
        $statistics = $seanceRepository->getStatistics();

        // Get all accepted courses for filter dropdown
        $courses = $courseRepository->findBy(['status' => 'accepted']);

        // If calendar view requested, render calendar template
        if ($view === 'calendar') {
            // Format seances for calendar JSON encoding
            $formattedSeances = array_map(function($seance) {
                $date = $seance->getDate();
                $startTime = $seance->getStartTime();
                $endTime = $seance->getEndTime();

                return [
                    'id' => $seance->getId(),
                    'title' => $seance->getTitle(),
                    'start' => $date->format('Y-m-d') . 'T' . $startTime->format('H:i:s'),
                    'end' => $date->format('Y-m-d') . 'T' . $endTime->format('H:i:s'),
                    'status' => $seance->getStatus(),
                    'location' => $seance->getLocation(),
                    'courseId' => $seance->getCourseId() ? $seance->getCourseId()->getId() : null,
                    'courseTitle' => $seance->getCourseId() ? $seance->getCourseId()->getTitle() : null,
                ];
            }, $seances);

            return $this->render('BackOffice/enseignant/seance/calendar.html.twig', [
                'seances' => $formattedSeances,
            ]);
        }

        return $this->render('BackOffice/enseignant/seance/index.html.twig', [
            'seances' => $seances,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'statistics' => $statistics,
            'courses' => $courses,
        ]);
    }

    // Admin only: Create new session for accepted courses
    #[Route('/new', name: 'app_seance_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, CourseRepository $courseRepository): Response
    {
        $seance = new Seance();

        // Pre-select course if passed via query parameter
        $courseId = $request->query->get('course');
        if ($courseId) {
            $course = $courseRepository->find($courseId);
            if ($course && $course->getStatus() === 'accepted') {
                $seance->setCourseId($course);
            }
        }

        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate that the course is accepted
            $course = $seance->getCourseId();
            if ($course && $course->getStatus() !== 'accepted') {
                $this->addFlash('error', 'You can only assign sessions to accepted courses!');
                return $this->render('BackOffice/enseignant/seance/new.html.twig', [
                    'seance' => $seance,
                    'form' => $form,
                ]);
            }

            $entityManager->persist($seance);
            $entityManager->flush();

            $this->addFlash('success', 'Session created successfully and assigned to the course!');
            return $this->redirectToRoute('app_seance_index');
        }

        return $this->render('BackOffice/enseignant/seance/new.html.twig', [
            'seance' => $seance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_seance_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Seance $seance): Response
    {
        return $this->render('BackOffice/enseignant/seance/show.html.twig', [
            'seance' => $seance,
        ]);
    }

    // Admin only: Edit session
    #[Route('/{id}/edit', name: 'app_seance_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SeanceType::class, $seance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate that the course is accepted
            $course = $seance->getCourseId();
            if ($course && $course->getStatus() !== 'accepted') {
                $this->addFlash('error', 'You can only assign sessions to accepted courses!');
                return $this->render('BackOffice/enseignant/seance/edit.html.twig', [
                    'seance' => $seance,
                    'form' => $form,
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Session updated successfully!');
            return $this->redirectToRoute('app_seance_index');
        }

        return $this->render('BackOffice/enseignant/seance/edit.html.twig', [
            'seance' => $seance,
            'form' => $form,
        ]);
    }

    // Admin only: Delete session
    #[Route('/{id}', name: 'app_seance_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Seance $seance, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$seance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($seance);
            $entityManager->flush();

            $this->addFlash('success', 'Session deleted successfully!');
        }

        return $this->redirectToRoute('app_seance_index');
    }
}