<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\SeanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        Request $request,
        CourseRepository $courseRepository,
        SeanceRepository $seanceRepository
    ): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Block parent/kid from accessing dashboard
        if (in_array($userRole, ['ROLE_PARENT', 'ROLE_KID'])) {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        // Get course statistics
        $statistics = $courseRepository->getCourseStatistics();
        
        // Get seance statistics (admin only)
        $seanceStatistics = null;
        if ($userRole === 'ROLE_ADMIN') {
            $seanceStatistics = $seanceRepository->getStatistics();
        }
        
        // Get recent courses (last 5)
        $recentCourses = $courseRepository->findBy([], ['id' => 'DESC'], 5);
        
        // Get pending courses for admin
        $pendingCourses = [];
        if ($userRole === 'ROLE_ADMIN') {
            $pendingCourses = $courseRepository->findBy(['status' => 'pending'], ['id' => 'DESC'], 10);
        }
        
        return $this->render('backoffice/dashboard.html.twig', [
            'userRole' => $userRole,
            'statistics' => $statistics,
            'seanceStatistics' => $seanceStatistics,
            'recentCourses' => $recentCourses,
            'pendingCourses' => $pendingCourses,
        ]);
    }
}
