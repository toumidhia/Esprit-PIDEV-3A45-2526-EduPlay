<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Subscription;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/course')]
final class CourseController extends AbstractController
{
    // Teacher: View their own courses
    // Admin: View all courses (pending/accepted/rejected)
    // Parent: View only accepted courses with assigned seances
    // Kid: View only courses parent subscribed them to
    #[Route(name: 'app_course_index', methods: ['GET'])]
    public function index(
        Request $request, 
        CourseRepository $courseRepository, 
        UserRepository $userRepository,
        SubscriptionRepository $subscriptionRepository
    ): Response
    {
        // TODO: Replace with actual user authentication
        // For now, use test user based on query parameter
        $testRole = $request->query->get('role', 'admin'); // admin, teacher, parent, kid
        $testUserId = match($testRole) {
            'teacher' => 1,
            'admin' => 2,
            'parent' => 3,
            'kid' => 4,
            default => 2
        };
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Get filter parameters from request
        $filters = [
            'search' => $request->query->get('search', ''),
            'status' => $request->query->get('status', ''),
            'level' => $request->query->get('level', ''),
            'duration' => $request->query->get('duration', ''),
        ];
        
        // Get sort parameters
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = $request->query->get('order', 'DESC');
        
        // Filter courses based on user role
        if ($userRole === 'ROLE_TEACHER') {
            // Teacher sees only their own courses
            // TODO: $courses = $courseRepository->findByTeacher($this->getUser(), $filters);
            $courses = $courseRepository->findWithFilters($filters, $sortBy, $sortOrder);
        } elseif ($userRole === 'ROLE_PARENT') {
            // Parents see only accepted courses with seances
            $courses = $courseRepository->findForParents($filters);
        } elseif ($userRole === 'ROLE_KID') {
            // Kids see only courses their parent subscribed them to
            $kidCourseIds = $subscriptionRepository->getKidCourseIds($testUserId);
            $courses = $courseRepository->findForKid($kidCourseIds, $filters);
        } else {
            // Admin sees all courses with filters
            $courses = $courseRepository->findWithFilters($filters, $sortBy, $sortOrder);
        }
        
        // Get statistics for admin dashboard
        $statistics = null;
        if ($userRole === 'ROLE_ADMIN') {
            $statistics = $courseRepository->getCourseStatistics();
        }
        
        // Get kids for parent (for subscription dropdown)
        $kids = [];
        if ($userRole === 'ROLE_PARENT') {
            $kids = $userRepository->findBy(['type' => 'kid']); // In production, filter by parent
        }
        
        // Get subscription status for each course (for parent view)
        $subscriptions = [];
        if ($userRole === 'ROLE_PARENT' && !empty($kids)) {
            foreach ($courses as $course) {
                foreach ($kids as $kid) {
                    $isSubscribed = $subscriptionRepository->isSubscribed($testUserId, $kid->getId(), $course->getId());
                    $subscriptions[$course->getId()][$kid->getId()] = $isSubscribed;
                }
            }
        }
        
        // Get all teachers for filter dropdown
        $teachers = $userRepository->findAll(); // TODO: Filter by role = 'ROLE_TEACHER'
        
        // Route to backoffice for admin/teacher, frontoffice for parent/kid
        $template = in_array($userRole, ['ROLE_ADMIN', 'ROLE_TEACHER']) 
            ? 'backoffice/course/index.html.twig' 
            : 'course/index.html.twig';
        
        return $this->render($template, [
            'courses' => $courses,
            'userRole' => $userRole,
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'statistics' => $statistics,
            'teachers' => $teachers,
            'kids' => $kids,
            'subscriptions' => $subscriptions,
            'currentUserId' => $testUserId,
        ]);
    }

    // Teacher: Create new course (status automatically set to 'pending')
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Only teachers can create courses
        if ($userRole !== 'ROLE_TEACHER') {
            $this->addFlash('error', 'Only teachers can create courses.');
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        $course = new Course();
        $course->setStatus('pending'); // Always pending when created by teacher
        
        $form = $this->createForm(CourseType::class, $course, [
            'is_teacher' => true, // Pass context to form
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle PDF file upload
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/courses',
                        $newFilename
                    );
                    $course->setPdfFile($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload PDF file: ' . $e->getMessage());
                }
            }
            
            // Set the current teacher as the course creator
            // TODO: Replace with actual user authentication: $course->setTeacherId($this->getUser());
            // For now, use test teacher user
            $testTeacher = $userRepository->findOneBy(['email' => 'teacher@eduplay.com']);
            if (!$testTeacher) {
                $this->addFlash('error', 'Test teacher not found. Please run: php bin/console app:create-test-users');
                return $this->redirectToRoute('app_course_new');
            }
            $course->setTeacherId($testTeacher);
            
            $entityManager->persist($course);
            $entityManager->flush();

            $this->addFlash('success', 'Course created successfully! It is pending admin approval.');
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backoffice/course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Request $request, Course $course): Response
    {
        // TODO: Add proper access control
        // Teachers can see their own courses
        // Parents can see accepted courses
        // Admin can see all
        
        // Determine role from request (temporary until auth is implemented)
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Route to backoffice for admin/teacher, frontoffice for parent/kid
        $template = in_array($userRole, ['ROLE_ADMIN', 'ROLE_TEACHER']) 
            ? 'backoffice/course/show.html.twig' 
            : 'course/show.html.twig';
        
        return $this->render($template, [
            'course' => $course,
            'userRole' => $userRole,
        ]);
    }

    // Download PDF file
    #[Route('/{id}/pdf', name: 'app_course_pdf', methods: ['GET'])]
    public function downloadPdf(Course $course): Response
    {
        if (!$course->getPdfFile()) {
            $this->addFlash('error', 'No PDF file available for this course.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/' . $course->getPdfFile();
        
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'PDF file not found.');
            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()]);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $course->getTitle() . '.pdf'
        );

        return $response;
    }

    // Teacher: Can edit only their own pending courses
    // Admin: Can edit any course and change status
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Block parent/kid from editing courses
        if (in_array($userRole, ['ROLE_PARENT', 'ROLE_KID'])) {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        // Teachers can only edit their own pending courses
        // if ($userRole === 'ROLE_TEACHER' && 
        //     ($course->getTeacherId() !== $this->getUser() || $course->getStatus() !== 'pending')) {
        //     throw $this->createAccessDeniedException();
        // }
        
        $form = $this->createForm(CourseType::class, $course, [
            'is_admin' => ($userRole === 'ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle PDF file upload
            $pdfFile = $form->get('pdfFile')->getData();
            if ($pdfFile) {
                // Delete old file if exists
                if ($course->getPdfFile()) {
                    $oldFile = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/' . $course->getPdfFile();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$pdfFile->guessExtension();

                try {
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/courses',
                        $newFilename
                    );
                    $course->setPdfFile($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload PDF file: ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Course updated successfully!');
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('backoffice/course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    // Admin: Accept a course
    #[Route('/{id}/accept', name: 'app_course_accept', methods: ['POST'])]
    public function accept(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Only admin can accept courses
        if ($userRole !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        if ($this->isCsrfTokenValid('accept'.$course->getId(), $request->request->get('_token'))) {
            $course->setStatus('accepted');
            $entityManager->flush();
            
            $this->addFlash('success', 'Course accepted successfully!');
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    // Admin: Reject a course
    #[Route('/{id}/reject', name: 'app_course_reject', methods: ['POST'])]
    public function reject(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Only admin can reject courses
        if ($userRole !== 'ROLE_ADMIN') {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        if ($this->isCsrfTokenValid('reject'.$course->getId(), $request->request->get('_token'))) {
            $course->setStatus('rejected');
            $entityManager->flush();
            
            $this->addFlash('warning', 'Course rejected.');
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    // Parent: Subscribe a kid to a course
    #[Route('/{id}/subscribe', name: 'app_course_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request, 
        Course $course, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Only parents can subscribe kids
        if ($userRole !== 'ROLE_PARENT') {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        if ($this->isCsrfTokenValid('subscribe'.$course->getId(), $request->request->get('_token'))) {
            $kidId = $request->request->get('kid_id');
            
            if (!$kidId) {
                $this->addFlash('error', 'Please select a kid to subscribe.');
                return $this->redirectToRoute('app_course_index');
            }
            
            $kid = $userRepository->find($kidId);
            
            if (!$kid || $kid->getType() !== 'kid') {
                $this->addFlash('error', 'Invalid kid selected.');
                return $this->redirectToRoute('app_course_index');
            }
            
            // TODO: Get actual parent user: $parent = $this->getUser();
            $parent = $userRepository->find(3); // Test parent ID
            
            $subscription = new Subscription();
            $subscription->setParentId($parent);
            $subscription->setKidId($kid);
            $subscription->setCourseId($course);
            $subscription->setSubscribedAt(new \DateTime());
            $subscription->setActive(true);
            
            $entityManager->persist($subscription);
            $entityManager->flush();
            
            $this->addFlash('success', $kid->getFirstName() . ' successfully subscribed to ' . $course->getTitle() . '!');
        }

        return $this->redirectToRoute('app_course_index', ['role' => 'parent']);
    }

    // Parent: Unsubscribe a kid from a course
    #[Route('/{id}/unsubscribe', name: 'app_course_unsubscribe', methods: ['POST'])]
    public function unsubscribe(
        Request $request, 
        Course $course, 
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subscriptionRepository
    ): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Only parents can unsubscribe kids
        if ($userRole !== 'ROLE_PARENT') {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        if ($this->isCsrfTokenValid('unsubscribe'.$course->getId(), $request->request->get('_token'))) {
            $kidId = $request->request->get('kid_id');
            $parentId = 3; // TODO: Get from $this->getUser()->getId()
            
            $subscription = $entityManager->getRepository(Subscription::class)->findOneBy([
                'parentId' => $parentId,
                'kidId' => $kidId,
                'courseId' => $course->getId(),
                'active' => true
            ]);
            
            if ($subscription) {
                $subscription->setActive(false);
                $entityManager->flush();
                
                $this->addFlash('warning', 'Subscription cancelled successfully.');
            }
        }

        return $this->redirectToRoute('app_course_index', ['role' => 'parent']);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Block parent/kid from deleting courses
        if (in_array($userRole, ['ROLE_PARENT', 'ROLE_KID'])) {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            // Delete PDF file if exists
            if ($course->getPdfFile()) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/courses/' . $course->getPdfFile();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $entityManager->remove($course);
            $entityManager->flush();
            
            $this->addFlash('success', 'Course deleted successfully!');
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
