<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\SeanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function index(
        CourseRepository $courseRepository,
        SeanceRepository $seanceRepository
    ): Response
    {
        $user = $this->getUser();
        $userRoles = $user->getRoles();

        // Get course statistics
        $statistics = $courseRepository->getCourseStatistics();

        // Get seance statistics (admin only)
        $seanceStatistics = null;
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $seanceStatistics = $seanceRepository->getStatistics();
        }

        // Get recent courses (last 5) - using the correct method name
        $recentCourses = $courseRepository->findRecentCourses(5);

        // Get pending courses for admin - using the correct method name
        $pendingCourses = [];
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $pendingCourses = $courseRepository->findPendingCourses(10);
        }

        return $this->render('BackOffice/admin/base_admin_stat.html.twig', [
            'userRole' => $this->getMainRole($userRoles),
            'statistics' => $statistics,
            'seanceStatistics' => $seanceStatistics,
            'recentCourses' => $recentCourses,
            'pendingCourses' => $pendingCourses,
        ]);
    }

    /**
     * Helper method to get the main role for template rendering
     */
    private function getMainRole(array $roles): string
    {
        $priorityRoles = ['ROLE_ADMIN', 'ROLE_ENSEIGNANT', 'ROLE_PARENT', 'ROLE_ENFANT', 'ROLE_USER'];

        foreach ($priorityRoles as $priorityRole) {
            if (in_array($priorityRole, $roles)) {
                return $priorityRole;
            }
        }

        return 'ROLE_USER';
    }
}