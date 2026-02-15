<?php
// src/Service/RecommendationCourseService.php

namespace App\Service;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Intelligent Recommendation Service
 *
 * Provides personalized course recommendations using multiple algorithms:
 * 1. Collaborative Filtering: "Kids like yours also enrolled in..."
 * 2. Demographic-Based: Popular courses for same age/level
 * 3. Content-Based: Similar to already enrolled courses
 * 4. Popularity-Based: Trending courses
 */
class RecommendationCourseService
{
    private EntityManagerInterface $entityManager;
    private CourseRepository $courseRepository;
    private SubscriptionRepository $subscriptionRepository;

    // Weights for hybrid scoring
    private const WEIGHT_COLLABORATIVE = 0.35;
    private const WEIGHT_DEMOGRAPHIC = 0.30;
    private const WEIGHT_CONTENT = 0.20;
    private const WEIGHT_POPULARITY = 0.15;

    public function __construct(
        EntityManagerInterface $entityManager,
        CourseRepository $courseRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->courseRepository = $courseRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Get personalized recommendations for a specific kid
     *
     * @param User $kid The kid to get recommendations for
     * @param int $limit Maximum number of recommendations
     * @return array Array of recommended courses with scores
     */
    public function getRecommendationsForKid(User $kid, int $limit = 5): array
    {
        if ($kid->getType() !== 'kid') { // CORRIGÉ: 'kid' au lieu de 'enfant'
            return [];
        }

        // Get all accepted courses
        $allCourses = $this->courseRepository->findBy(['status' => 'accepted']);

        // Get kid's current enrollments
        $enrolledCourses = $this->getEnrolledCourses($kid);
        $enrolledIds = array_map(fn($c) => $c->getId(), $enrolledCourses);

        // Filter out already enrolled courses
        $availableCourses = array_filter($allCourses, fn($c) => !in_array($c->getId(), $enrolledIds));

        // Calculate scores for each course
        $scoredCourses = [];
        foreach ($availableCourses as $course) {
            $score = $this->calculateHybridScore($kid, $course, $enrolledCourses);
            $scoredCourses[] = [
                'course' => $course,
                'score' => $score,
                'reasons' => $this->getRecommendationReasons($kid, $course, $score)
            ];
        }

        // Sort by score descending
        usort($scoredCourses, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scoredCourses, 0, $limit);
    }

    /**
     * Get recommendations for all kids of a parent
     *
     * @param User $parent The parent user
     * @param int $limitPerKid Recommendations per kid
     * @return array Recommendations grouped by kid
     */
    public function getRecommendationsForParent(User $parent, int $limitPerKid = 3): array
    {
        if ($parent->getType() !== 'parent') {
            return [];
        }

        $recommendations = [];
        foreach ($parent->getEnfants() as $kid) {
            $kidRecs = $this->getRecommendationsForKid($kid, $limitPerKid);
            if (!empty($kidRecs)) {
                $recommendations[] = [
                    'kid' => $kid,
                    'recommendations' => $kidRecs
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Calculate hybrid recommendation score using multiple algorithms
     */
    private function calculateHybridScore(User $kid, Course $course, array $enrolledCourses): float
    {
        $collaborativeScore = $this->getCollaborativeScore($kid, $course);
        $demographicScore = $this->getDemographicScore($kid, $course);
        $contentScore = $this->getContentBasedScore($course, $enrolledCourses);
        $popularityScore = $this->getPopularityScore($course);

        return (
            self::WEIGHT_COLLABORATIVE * $collaborativeScore +
            self::WEIGHT_DEMOGRAPHIC * $demographicScore +
            self::WEIGHT_CONTENT * $contentScore +
            self::WEIGHT_POPULARITY * $popularityScore
        );
    }

    /**
     * Collaborative Filtering: Find kids with similar enrollments
     */
    private function getCollaborativeScore(User $kid, Course $course): float
    {
        // Get courses this kid is enrolled in
        $kidCourses = $this->getEnrolledCourses($kid);

        if (empty($kidCourses)) {
            return 0.5; // Neutral score for new kids
        }

        // Find other kids enrolled in ANY of the same courses
        $similarKids = $this->findSimilarKids($kid, $kidCourses);

        if (empty($similarKids)) {
            return 0.3;
        }

        // Count how many similar kids are enrolled in the target course
        $enrollmentCount = 0;
        foreach ($similarKids as $similarKid) {
            if ($this->isKidEnrolledInCourse($similarKid, $course)) {
                $enrollmentCount++;
            }
        }

        // Return percentage as score (0-1)
        return min(1.0, $enrollmentCount / count($similarKids));
    }

    /**
     * Demographic-Based: Popular courses for kids of similar school level
     */
    private function getDemographicScore(User $kid, Course $course): float
    {
        $niveau = $kid->getNiveau();

        if (!$niveau) {
            return 0.5;
        }

        // Find kids in same school level (niveau)
        $demographicKids = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.type = :type')
            ->andWhere('u.niveau = :niveau')
            ->andWhere('u.id != :kidId')
            ->setParameter('type', 'kid') // CORRIGÉ: 'kid' au lieu de 'enfant'
            ->setParameter('niveau', $niveau)
            ->setParameter('kidId', $kid->getId())
            ->getQuery()
            ->getResult();

        if (empty($demographicKids)) {
            return 0.5;
        }

        // Count enrollments in this course from demographic peers
        $enrollmentCount = 0;
        foreach ($demographicKids as $peer) {
            if ($this->isKidEnrolledInCourse($peer, $course)) {
                $enrollmentCount++;
            }
        }

        return min(1.0, $enrollmentCount / max(1, count($demographicKids)));
    }

    /**
     * Content-Based: Find courses similar to what kid already likes
     */
    private function getContentBasedScore(Course $course, array $enrolledCourses): float
    {
        if (empty($enrolledCourses)) {
            return 0.5; // Neutral for new kids
        }

        $similarityScore = 0;
        foreach ($enrolledCourses as $enrolledCourse) {
            // Compare level
            if ($course->getLevel() === $enrolledCourse->getLevel()) {
                $similarityScore += 0.4;
            }

            // Compare duration (convert to int for comparison)
            $courseDuration = (int) $course->getDurationTraining();
            $enrolledDuration = (int) $enrolledCourse->getDurationTraining();
            $durationDiff = abs($courseDuration - $enrolledDuration);
            if ($durationDiff <= 2) {
                $similarityScore += 0.3;
            }

            // Compare teacher (kids might like specific teachers)
            if ($course->getTeacherId()->getId() === $enrolledCourse->getTeacherId()->getId()) {
                $similarityScore += 0.3;
            }
        }

        // Average similarity across enrolled courses
        return min(1.0, $similarityScore / count($enrolledCourses));
    }

    /**
     * Popularity-Based: Overall trending courses
     */
    private function getPopularityScore(Course $course): float
    {
        $enrollmentCount = $this->subscriptionRepository->count([
            'course' => $course,
            'active' => true
        ]);

        // Get max enrollments for normalization
        $maxEnrollments = $this->getMaxEnrollments();

        if ($maxEnrollments === 0) {
            return 0.5;
        }

        // Normalize to 0-1 range
        return min(1.0, $enrollmentCount / $maxEnrollments);
    }

    /**
     * Generate human-readable recommendation reasons
     */
    private function getRecommendationReasons(User $kid, Course $course, float $score): array
    {
        $reasons = [];

        // Check demographic popularity
        $niveau = $kid->getNiveau();
        if ($niveau) {
            $demographicCount = $this->getDemographicEnrollmentCount($niveau, $course);
            if ($demographicCount > 0) {
                $reasons[] = "Populaire auprès des enfants en {$niveau}";
            }
        }

        // Check overall popularity
        $totalEnrollments = $this->subscriptionRepository->count([
            'course' => $course,
            'active' => true
        ]);
        if ($totalEnrollments >= 5) {
            $reasons[] = "{$totalEnrollments} enfants déjà inscrits";
        }

        // Check level appropriateness
        $enrolledCourses = $this->getEnrolledCourses($kid);
        $levelMatch = false;
        foreach ($enrolledCourses as $enrolled) {
            if ($enrolled->getLevel() === $course->getLevel()) {
                $levelMatch = true;
                break;
            }
        }
        if ($levelMatch) {
            $reasons[] = "Correspond au niveau actuel de votre enfant";
        }

        // Check age appropriateness (nouveau)
        $age = $kid->getAge();
        if ($age) {
            $ageScore = $this->calculateAgeAppropriateness($course->getLevel(), $age);
            if ($ageScore > 0.7) {
                $reasons[] = "Idéal pour les enfants de {$age} ans";
            }
        }

        // Check if new course
        $createdAt = $course->getCreatedAt();
        if ($createdAt) {
            $courseAge = (new \DateTime())->diff($createdAt)->days;
            if ($courseAge <= 30) {
                $reasons[] = "Nouveau cours ajouté récemment";
            }
        }

        // High score reason
        if ($score >= 0.7) {
            $reasons[] = "Fortement recommandé pour votre enfant";
        }

        return array_slice($reasons, 0, 3); // Max 3 reasons
    }

    /**
     * Calculate age appropriateness score
     */
    private function calculateAgeAppropriateness(string $courseLevel, int $kidAge): float
    {
        $ageRanges = [
            'Beginner' => ['min' => 6, 'max' => 9, 'ideal' => 8],
            'Intermediate' => ['min' => 9, 'max' => 12, 'ideal' => 10],
            'Advanced' => ['min' => 11, 'max' => 15, 'ideal' => 13]
        ];

        $range = $ageRanges[$courseLevel] ?? ['min' => 6, 'max' => 15, 'ideal' => 10];

        if ($kidAge < $range['min']) {
            return 0.3;
        }
        if ($kidAge > $range['max']) {
            return 0.4;
        }
        if ($kidAge == $range['ideal']) {
            return 1.0;
        }

        $distance = abs($kidAge - $range['ideal']);
        $maxDistance = max($range['ideal'] - $range['min'], $range['max'] - $range['ideal']);

        return 0.5 + (0.5 * (1 - ($distance / $maxDistance)));
    }

    // Helper methods

    private function getEnrolledCourses(User $kid): array
    {
        $subscriptions = $this->subscriptionRepository->findBy([
            'kid' => $kid,
            'active' => true
        ]);

        return array_map(fn($s) => $s->getCourse(), $subscriptions);
    }

    private function findSimilarKids(User $kid, array $kidCourses): array
    {
        if (empty($kidCourses)) {
            return [];
        }

        $courseIds = array_map(fn($c) => $c->getId(), $kidCourses);

        // Find kids enrolled in at least one of the same courses
        $similarKids = $this->entityManager->createQuery(
            'SELECT DISTINCT u FROM App\Entity\User u
             JOIN App\Entity\Subscription s WITH s.kid = u
             WHERE s.course IN (:courses)
             AND s.active = true
             AND u.id != :kidId'
        )
            ->setParameter('courses', $courseIds)
            ->setParameter('kidId', $kid->getId())
            ->getResult();

        return $similarKids;
    }

    private function isKidEnrolledInCourse(User $kid, Course $course): bool
    {
        $subscription = $this->subscriptionRepository->findOneBy([
            'kid' => $kid,
            'course' => $course,
            'active' => true
        ]);

        return $subscription !== null;
    }

    private function getMaxEnrollments(): int
    {
        $result = $this->entityManager->createQuery(
            'SELECT COUNT(s.id) as enrollments FROM App\Entity\Subscription s
             WHERE s.active = true
             GROUP BY s.course
             ORDER BY enrollments DESC'
        )
            ->setMaxResults(1)
            ->getOneOrNullResult();

        return $result['enrollments'] ?? 1;
    }

    private function getDemographicEnrollmentCount(string $niveau, Course $course): int
    {
        $result = $this->entityManager->createQuery(
            'SELECT COUNT(s.id) FROM App\Entity\Subscription s
             JOIN s.kid k
             WHERE s.course = :course
             AND s.active = true
             AND k.niveau = :niveau'
        )
            ->setParameter('course', $course)
            ->setParameter('niveau', $niveau)
            ->getSingleScalarResult();

        return (int) $result;
    }
}