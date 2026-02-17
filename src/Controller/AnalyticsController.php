<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\SubscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/analytics')]
#[IsGranted('ROLE_ADMIN')]
class AnalyticsController extends AbstractController
{
    /**
     * Main analytics dashboard
     */
    #[Route('', name: 'app_analytics_dashboard')]
    public function dashboard(CourseRepository $courseRepository, SubscriptionRepository $subscriptionRepository): Response
    {
        // Get comprehensive statistics
        $stats = $this->getComprehensiveStats($courseRepository, $subscriptionRepository);

        return $this->render('BackOffice/admin/analytics/dashboard.html.twig', $stats);
    }

    /**
     * API endpoint for chart data
     */
    #[Route('/api/chart-data', name: 'app_analytics_chart_data')]
    public function chartData(CourseRepository $courseRepository, SubscriptionRepository $subscriptionRepository): JsonResponse
    {
        // Level distribution for pie chart
        $levelDistribution = $courseRepository->createQueryBuilder('c')
            ->select('c.level', 'COUNT(c.id) as count')
            ->where('c.status = :status')
            ->setParameter('status', 'accepted')
            ->groupBy('c.level')
            ->getQuery()
            ->getResult();

        // Status distribution
        $statusDistribution = $courseRepository->createQueryBuilder('c')
            ->select('c.status', 'COUNT(c.id) as count')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        // Subscriptions per course (top 10)
        $subscriptionsPerCourse = $courseRepository->createQueryBuilder('c')
            ->select('c.title', 'COUNT(s.id) as subscriptionCount')
            ->leftJoin('c.subscriptions', 's', 'WITH', 's.active = true')
            ->where('c.status = :status')
            ->setParameter('status', 'accepted')
            ->groupBy('c.id')
            ->orderBy('subscriptionCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'levelDistribution' => $levelDistribution,
            'statusDistribution' => $statusDistribution,
            'subscriptionsPerCourse' => $subscriptionsPerCourse,
        ]);
    }

    /**
     * Course participants analytics
     */
    #[Route('/course/{id}/participants', name: 'app_analytics_course_participants')]
    public function courseParticipants(int $id, CourseRepository $courseRepository, SubscriptionRepository $subscriptionRepository): Response
    {
        $course = $courseRepository->find($id);

        if (!$course) {
            throw $this->createNotFoundException();
        }

        // Get all subscriptions for this course
        $subscriptions = $subscriptionRepository->findBy(
            ['course' => $course, 'active' => true],
            ['subscribedAt' => 'DESC']
        );

        // Age distribution of participants
        $ageDistribution = [];
        foreach ($subscriptions as $subscription) {
            $kid = $subscription->getKid();
            $age = $kid->getAge() ?? 0;
            $ageGroup = floor($age / 2) * 2 . '-' . (floor($age / 2) * 2 + 1);
            
            if (!isset($ageDistribution[$ageGroup])) {
                $ageDistribution[$ageGroup] = 0;
            }
            $ageDistribution[$ageGroup]++;
        }

        // Level distribution of participants
        $levelDistribution = [];
        foreach ($subscriptions as $subscription) {
            $kid = $subscription->getKid();
            $level = $kid->getNiveau() ?? 'Non spécifié';
            
            if (!isset($levelDistribution[$level])) {
                $levelDistribution[$level] = 0;
            }
            $levelDistribution[$level]++;
        }

        return $this->render('BackOffice/admin/analytics/course_participants.html.twig', [
            'course' => $course,
            'subscriptions' => $subscriptions,
            'totalParticipants' => count($subscriptions),
            'ageDistribution' => $ageDistribution,
            'levelDistribution' => $levelDistribution,
        ]);
    }

    /**
     * Performance metrics
     */
    #[Route('/performance', name: 'app_analytics_performance')]
    public function performance(CourseRepository $courseRepository, SubscriptionRepository $subscriptionRepository): Response
    {
        // Most popular courses
        $popularCourses = $courseRepository->createQueryBuilder('c')
            ->select('c', 'COUNT(s.id) as subscriptionCount')
            ->leftJoin('c.subscriptions', 's', 'WITH', 's.active = true')
            ->where('c.status = :status')
            ->setParameter('status', 'accepted')
            ->groupBy('c.id')
            ->orderBy('subscriptionCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Least popular courses (need attention)
        $unpopularCourses = $courseRepository->createQueryBuilder('c')
            ->select('c', 'COUNT(s.id) as subscriptionCount')
            ->leftJoin('c.subscriptions', 's', 'WITH', 's.active = true')
            ->where('c.status = :status')
            ->setParameter('status', 'accepted')
            ->groupBy('c.id')
            ->orderBy('subscriptionCount', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Average subscriptions per course
        $totalSubscriptions = $subscriptionRepository->count(['active' => true]);
        $totalAcceptedCourses = $courseRepository->count(['status' => 'accepted']);
        $avgSubscriptions = $totalAcceptedCourses > 0 ? $totalSubscriptions / $totalAcceptedCourses : 0;

        return $this->render('BackOffice/admin/analytics/performance.html.twig', [
            'popularCourses' => $popularCourses,
            'unpopularCourses' => $unpopularCourses,
            'avgSubscriptions' => round($avgSubscriptions, 2),
        ]);
    }

    private function getComprehensiveStats(CourseRepository $courseRepository, SubscriptionRepository $subscriptionRepository): array
    {
        return [
            'totalCourses' => $courseRepository->count([]),
            'activeCourses' => $courseRepository->count(['status' => 'accepted']),
            'pendingCourses' => $courseRepository->count(['status' => 'pending']),
            'rejectedCourses' => $courseRepository->count(['status' =>'rejected']),
            'totalSubscriptions' => $subscriptionRepository->count(['active' => true]),
            'beginnerCourses' => $courseRepository->count(['level' => 'Beginner', 'status' => 'accepted']),
            'intermediateCourses' => $courseRepository->count(['level' => 'Intermediate', 'status' => 'accepted']),
            'advancedCourses' => $courseRepository->count(['level' => 'Advanced', 'status' => 'accepted']),
        ];
    }
}
