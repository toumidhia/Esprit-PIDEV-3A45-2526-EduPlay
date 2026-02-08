<?php

namespace App\Controller\Front;

use App\Repository\SchoolEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/events', name: 'front_event_index', methods: ['GET'])]
    public function index(SchoolEventRepository $schoolEventRepository): Response
    {
        $events = $schoolEventRepository->findBy([], ['startDate' => 'ASC']);

        return $this->render('front/event/index.html.twig', [
            'events' => $events,
        ]);
    }
}
