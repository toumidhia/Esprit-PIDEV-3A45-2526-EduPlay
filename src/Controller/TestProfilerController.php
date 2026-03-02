<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestProfilerController extends AbstractController
{
    #[Route('/test-profiler', name: 'app_test_profiler')]
    public function index(): Response
    {
        return $this->render('test_profiler.html.twig');
    }
}