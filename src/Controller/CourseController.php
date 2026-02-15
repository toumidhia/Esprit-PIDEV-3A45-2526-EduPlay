<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Subscription;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use App\Repository\SubscriptionRepository;
use App\Service\RecommendationCourseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
#[Route('/course')]
final class CourseController extends AbstractController
{

    private $emailService;
    private $userRepository;
    private $logger;

    public function __construct(
        EmailService $emailService,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        $this->emailService = $emailService;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    #[Route(name: 'app_course_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(
        Request $request,
        CourseRepository $courseRepository,
        UserRepository $userRepository,
        SubscriptionRepository $subscriptionRepository,
        RecommendationCourseService $RecommendationCourseService
    ): Response
    {
        // Check if user is authenticated
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Please login to access courses.');
            return $this->redirectToRoute('app_login');
        }

        $userRoles = $user->getRoles();

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
        if (in_array('ROLE_TEACHER', $userRoles)) {
            // Teacher sees only their own courses
            $courses = $courseRepository->findByTeacher($user, $filters);
            $template = 'backoffice/course/index.html.twig';
            $recommendations = [];
        } elseif (in_array('ROLE_PARENT', $userRoles)) {
            // Parents see only accepted courses
            $courses = $courseRepository->findBy(['status' => 'accepted'], ['title' => 'ASC']);
            $template = 'FrontOffice/course/browse.html.twig';

            // Get parent's kids for subscriptions
            $kids = $userRepository->findBy(['parent' => $user, 'type' => 'kid']);

            // Get recommendations for each kid
            $recommendations = [];
            foreach ($kids as $kid) {
                $kidRecs = $RecommendationCourseService->getRecommendationsForKid($kid, 4);
                if (!empty($kidRecs)) {
                    $recommendations[] = [
                        'kid' => $kid,
                        'recommendations' => $kidRecs
                    ];
                }
            }
        } elseif (in_array('ROLE_KID', $userRoles)) {
            // Kids see only courses they are subscribed to
            $subscriptions = $subscriptionRepository->findActiveSubscriptionsByKid($user->getId());
            $courses = [];
            foreach ($subscriptions as $subscription) {
                $courses[] = $subscription->getCourse();
            }
            $template = 'FrontOffice/course/browse.html.twig';
            $kids = [];

            // Get recommendations for this kid (courses they are NOT subscribed to)
            $recommendations = $RecommendationCourseService->getRecommendationsForKid($user, 6);
        } else {
            // Admin sees all courses with filters
            $courses = $courseRepository->findAll();
            $template = 'backoffice/course/index.html.twig';
            $recommendations = [];
            $kids = [];
        }

        // Get statistics for admin dashboard
        $statistics = null;
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $statistics = $courseRepository->getCourseStatistics();
        }

        // Get kids for parent (for subscription dropdown)
        $kids = $kids ?? [];
        if (in_array('ROLE_PARENT', $userRoles) && empty($kids)) {
            $kids = $userRepository->findBy(['parent' => $user, 'type' => 'kid']);
        }

        // Get subscription status for each course (for parent view)
        $subscriptionMap = [];
        if (in_array('ROLE_PARENT', $userRoles) && !empty($kids)) {
            // Get all active subscriptions for this parent
            $activeSubscriptions = $subscriptionRepository->findActiveSubscriptionsByParent($user->getId());

            foreach ($activeSubscriptions as $subscription) {
                $subscriptionMap[$subscription->getCourse()->getId()][$subscription->getKid()->getId()] = true;
            }
        }

        // Get all teachers for filter dropdown
        $teachers = $userRepository->findBy(['type' => 'teacher']);

        return $this->render($template, [
            'courses' => $courses,
            'userRole' => $this->getMainRole($userRoles),
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'statistics' => $statistics,
            'teachers' => $teachers,
            'kids' => $kids,
            'subscriptions' => $subscriptionMap,
            'currentUserId' => $user->getId(),
            'recommendations' => $recommendations,
            'isKidView' => in_array('ROLE_KID', $userRoles)
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('=== DÉBUT CRÉATION COURS ===');

        $course = new Course();
        $course->setStatus('pending');
        $course->setTeacherId($this->getUser());

        $form = $this->createForm(CourseType::class, $course, [
            'is_teacher' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle PDF file upload with manual validation
            $pdfFile = $form->get('pdfFile')->getData();

            if ($pdfFile) {
                // Manual file size check (10MB = 10485760 bytes)
                if ($pdfFile->getSize() > 10485760) {
                    $this->addFlash('error', 'Le fichier est trop grand (max 10MB)');
                    return $this->redirectToRoute('app_course_new');
                }

                // Manual extension check
                $originalName = $pdfFile->getClientOriginalName();
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                if ($extension !== 'pdf') {
                    $this->addFlash('error', 'Veuillez télécharger un fichier PDF valide (.pdf)');
                    return $this->redirectToRoute('app_course_new');
                }

                // Generate unique filename
                $originalFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                try {
                    // Move the file to uploads directory
                    $pdfFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/courses',
                        $newFilename
                    );
                    $course->setPdfFile($newFilename);
                    $this->logger->info('PDF uploadé avec succès: ' . $newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Échec du téléchargement du fichier PDF: ' . $e->getMessage());
                    return $this->redirectToRoute('app_course_new');
                }
            }

            // ÉTAPE 1: Sauvegarder le cours en base de données pour obtenir un ID
            $entityManager->persist($course);
            $entityManager->flush();  // Le cours a maintenant un ID
            $this->logger->info('Cours sauvegardé avec ID: ' . $course->getId());

            // ÉTAPE 2: Maintenant que le cours a un ID, envoyer les emails
            try {
                $this->logger->info('ÉTAPE 1: Récupération des parents...');
                $parents = $this->userRepository->findAllParents();
                $this->logger->info('Parents trouvés: ' . count($parents));

                // Log chaque parent pour déboguer
                foreach ($parents as $index => $parent) {
                    $this->logger->info(sprintf('Parent %d: %s (%s %s)',
                        $index+1,
                        $parent->getEmail(),
                        $parent->getFirstName(),
                        $parent->getLastName()
                    ));
                }

                if (!empty($parents)) {
                    $this->logger->info('ÉTAPE 2: Appel du service EmailService...');

                    // Vérifier que le service existe
                    $this->logger->info('Service EmailService: ' . get_class($this->emailService));

                    // Envoyer les notifications avec le cours qui a maintenant un ID
                    $this->emailService->sendNewCourseNotification($course, $parents);

                    $this->logger->info('ÉTAPE 3: EmailService exécuté avec succès');

                    $this->addFlash('success', sprintf(
                        'Cours créé avec succès! %d parent(s) ont été notifié(s) par email.',
                        count($parents)
                    ));
                } else {
                    $this->logger->warning('Aucun parent trouvé dans la base de données');
                    $this->addFlash('warning', 'Cours créé mais aucun parent trouvé dans la base de données.');
                }
            } catch (\Exception $e) {
                $this->logger->error('ERREUR ENVOI EMAILS: ' . $e->getMessage());
                $this->logger->error('Trace: ' . $e->getTraceAsString());
                $this->addFlash('warning', 'Cours créé mais erreur lors de l\'envoi des notifications: ' . $e->getMessage());
            }

            $this->logger->info('=== FIN CRÉATION COURS ===');
            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('backoffice/course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/course/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, SubscriptionRepository $subscriptionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userRoles = $user->getRoles();

        // Check permissions based on user role
        if (in_array('ROLE_TEACHER', $userRoles)) {
            // Teachers can only see their own courses
            if ($course->getTeacherId() !== $user) {
                $this->addFlash('error', 'Vous ne pouvez voir que vos propres cours.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_PARENT', $userRoles)) {
            // Parents can only see accepted courses
            if ($course->getStatus() !== 'accepted') {
                $this->addFlash('error', 'Vous ne pouvez voir que les cours acceptés.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_KID', $userRoles)) {
            // Kids can only see courses they are subscribed to
            // FIX: Use isKidSubscribedToCourse instead of isSubscribed
            $isSubscribed = $subscriptionRepository->isKidSubscribedToCourse(
                $user->getId(), // Kid ID
                $course->getId() // Course ID
            );
            if (!$isSubscribed) {
                $this->addFlash('error', 'Vous n\'êtes pas inscrit à ce cours.');
                return $this->redirectToRoute('app_course_index');
            }
        }
        // Admin can see all courses

        // Route to backoffice for admin/teacher, frontoffice for parent/kid
        $template = (in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_TEACHER', $userRoles))
            ? 'backoffice/course/show.html.twig'
            : 'FrontOffice/course/show.html.twig';

        return $this->render($template, [
            'course' => $course,
            'userRole' => $this->getMainRole($userRoles),
        ]);
    }

    // Download PDF file
    #[Route('/{id}/pdf', name: 'app_course_pdf', methods: ['GET'])]
    public function downloadPdf(Course $course, SubscriptionRepository $subscriptionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Check permissions
        $userRoles = $user->getRoles();
        if (in_array('ROLE_KID', $userRoles)) {
            // Kids can only download PDFs of courses they're subscribed to
            $isSubscribed = $subscriptionRepository->isSubscribed(
                $user->getId(),
                $user->getId(),
                $course->getId()
            );
            if (!$isSubscribed) {
                $this->addFlash('error', 'You are not subscribed to this course.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_PARENT', $userRoles)) {
            // Parents can only download PDFs of accepted courses
            if ($course->getStatus() !== 'accepted') {
                $this->addFlash('error', 'This course is not available for download.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_TEACHER', $userRoles)) {
            // Teachers can only download PDFs of their own courses
            if ($course->getTeacherId() !== $user) {
                $this->addFlash('error', 'You can only download PDFs of your own courses.');
                return $this->redirectToRoute('app_course_index');
            }
        }
        // Admin can download all PDFs

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
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userRoles = $user->getRoles();

        // Check permissions
        if (in_array('ROLE_TEACHER', $userRoles)) {
            // Teachers can only edit their own pending courses
            if ($course->getTeacherId() !== $user) {
                $this->addFlash('error', 'You can only edit your own courses.');
                return $this->redirectToRoute('app_course_index');
            }
            if ($course->getStatus() !== 'pending') {
                $this->addFlash('error', 'You can only edit pending courses.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_PARENT', $userRoles) || in_array('ROLE_KID', $userRoles)) {
            // Parents and kids cannot edit courses
            $this->addFlash('error', 'You do not have permission to edit courses.');
            return $this->redirectToRoute('app_course_index');
        }

        $isAdmin = in_array('ROLE_ADMIN', $userRoles);

        $form = $this->createForm(CourseType::class, $course, [
            'is_admin' => $isAdmin,
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
            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('backoffice/course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    // Admin: Accept a course
    #[Route('/{id}/accept', name: 'app_course_accept', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function accept(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('accept'.$course->getId(), $request->request->get('_token'))) {
            $course->setStatus('accepted');
            $entityManager->flush();

            $this->addFlash('success', 'Course accepted successfully!');
        }

        return $this->redirectToRoute('app_course_index');
    }

    // Admin: Reject a course
    #[Route('/{id}/reject', name: 'app_course_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reject'.$course->getId(), $request->request->get('_token'))) {
            $course->setStatus('rejected');
            $entityManager->flush();

            $this->addFlash('warning', 'Course rejected.');
        }

        return $this->redirectToRoute('app_course_index');
    }

    // Parent: Subscribe a kid to a course
    #[Route('/{id}/subscribe', name: 'app_course_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_PARENT')]
    public function subscribe(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('subscribe'.$course->getId(), $request->request->get('_token'))) {
            $kidId = $request->request->get('kid_id');

            if (!$kidId) {
                $this->addFlash('error', 'Veuillez sélectionner un enfant.');
                return $this->redirectToRoute('app_course_index');
            }

            $kid = $userRepository->find($kidId);

            if (!$kid || $kid->getType() !== 'kid') {
                $this->addFlash('error', 'Enfant invalide.');
                return $this->redirectToRoute('app_course_index');
            }

            $parent = $this->getUser();

            // Check if already subscribed
            $existingSubscription = $entityManager->getRepository(Subscription::class)->findOneBy([
                'parent' => $parent,
                'kid' => $kid,
                'course' => $course,
                'active' => true
            ]);

            if ($existingSubscription) {
                $this->addFlash('warning', $kid->getFirstName() . ' est déjà inscrit à ce cours.');
                return $this->redirectToRoute('app_course_index');
            }

            $subscription = new Subscription();
            $subscription->setParent($parent);
            $subscription->setKid($kid);
            $subscription->setCourse($course);
            $subscription->setActive(true);
            // subscribedAt is automatically set in the Subscription constructor
            // DO NOT call setCreatedAt() or setUpdatedAt() - these methods don't exist!

            $entityManager->persist($subscription);
            $entityManager->flush();

            $this->addFlash('success', $kid->getFirstName() . ' a été inscrit avec succès à ' . $course->getTitle() . '!');
        }

        return $this->redirectToRoute('app_course_index');
    }


    // Parent: Unsubscribe a kid from a course
    #[Route('/{id}/unsubscribe', name: 'app_course_unsubscribe', methods: ['POST'])]
    #[IsGranted('ROLE_PARENT')]
    public function unsubscribe(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('unsubscribe'.$course->getId(), $request->request->get('_token'))) {
            $kidId = $request->request->get('kid_id');
            $parent = $this->getUser();

            $subscription = $entityManager->getRepository(Subscription::class)->findOneBy([
                'parent' => $parent,
                'kid' => $kidId,
                'course' => $course,
                'active' => true
            ]);

            if ($subscription) {
                $subscription->setActive(false);
                $entityManager->flush();

                $this->addFlash('warning', 'Désinscription réussie.');
            } else {
                $this->addFlash('error', 'Inscription non trouvée.');
            }
        }

        return $this->redirectToRoute('app_course_index');
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userRoles = $user->getRoles();

        // Check permissions
        if (in_array('ROLE_TEACHER', $userRoles)) {
            // Teachers can only delete their own pending courses
            if ($course->getTeacherId() !== $user) {
                $this->addFlash('error', 'You can only delete your own courses.');
                return $this->redirectToRoute('app_course_index');
            }
            if ($course->getStatus() !== 'pending') {
                $this->addFlash('error', 'You can only delete pending courses.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_PARENT', $userRoles) || in_array('ROLE_KID', $userRoles)) {
            // Parents and kids cannot delete courses
            $this->addFlash('error', 'You do not have permission to delete courses.');
            return $this->redirectToRoute('app_course_index');
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

        return $this->redirectToRoute('app_course_index');
    }

    /**
     * Helper method to get the main role for template rendering
     */
    private function getMainRole(array $roles): string
    {
        $priorityRoles = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_PARENT', 'ROLE_KID', 'ROLE_USER'];

        foreach ($priorityRoles as $priorityRole) {
            if (in_array($priorityRole, $roles)) {
                return $priorityRole;
            }
        }

        return 'ROLE_USER';
    }

    #[Route('/parent/browse', name: 'app_course_parent_browse')]
    #[IsGranted('ROLE_PARENT')]
    public function parentBrowse(
        CourseRepository $courseRepository,
        UserRepository $userRepository,
        SubscriptionRepository $subscriptionRepository,
        RecommendationCourseService $RecommendationCourseService
    ): Response {
        /** @var \App\Entity\User $parent */
        $parent = $this->getUser();

        // Get only accepted courses
        $courses = $courseRepository->findBy(['status' => 'accepted'], ['title' => 'ASC']);

        // Get parent's kids
        $kids = $userRepository->findBy(['parent' => $parent, 'type' => 'kid']);

        // Get existing subscriptions
        $activeSubscriptions = $subscriptionRepository->findActiveSubscriptionsByParent($parent->getId());
        $subscriptionMap = [];

        foreach ($activeSubscriptions as $subscription) {
            $subscriptionMap[$subscription->getCourse()->getId()][$subscription->getKid()->getId()] = true;
        }

        // AI RECOMMENDATIONS for parent
        $recommendations = [];
        foreach ($kids as $kid) {
            $kidRecs = $RecommendationCourseService->getRecommendationsForKid($kid, 4);
            if (!empty($kidRecs)) {
                $recommendations[] = [
                    'kid' => $kid,
                    'recommendations' => $kidRecs
                ];
            }
        }

        return $this->render('FrontOffice/course/browse.html.twig', [
            'courses' => $courses,
            'kids' => $kids,
            'subscriptions' => $subscriptionMap,
            'parent' => $parent,
            'recommendations' => $recommendations,
            'isKidView' => false
        ]);
    }
    #[Route('/detail/{id}', name: 'app_course_detail', methods: ['GET'])]
    public function detail(
        int $id,
        CourseRepository $courseRepository,
        SubscriptionRepository $subscriptionRepository,
        UserRepository $userRepository
    ): Response
    {
        $course = $courseRepository->find($id);

        if (!$course) {
            throw $this->createNotFoundException('Cours non trouvé');
        }

        $user = $this->getUser();
        $userRoles = $user ? $user->getRoles() : [];

        // Check permissions
        if (in_array('ROLE_KID', $userRoles)) {
            // Kids can only see courses they are subscribed to
            $isSubscribed = $subscriptionRepository->isKidSubscribedToCourse(
                $user->getId(),
                $course->getId()
            );
            if (!$isSubscribed) {
                $this->addFlash('error', 'Vous n\'êtes pas inscrit à ce cours.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_PARENT', $userRoles)) {
            // Parents can only see accepted courses
            if ($course->getStatus() !== 'accepted') {
                $this->addFlash('error', 'Ce cours n\'est pas disponible.');
                return $this->redirectToRoute('app_course_index');
            }
        } elseif (in_array('ROLE_TEACHER', $userRoles)) {
            // Teachers can only see their own courses
            if ($course->getTeacherId() !== $user) {
                $this->addFlash('error', 'Vous ne pouvez voir que vos propres cours.');
                return $this->redirectToRoute('app_course_index');
            }
        } else {
            // Admin or not logged in - check if course is accepted
            if ($course->getStatus() !== 'accepted') {
                $this->addFlash('error', 'Ce cours n\'est pas disponible.');
                return $this->redirectToRoute('app_course_index');
            }
        }

        // Get kids if user is parent
        $kids = [];
        $kidSubscriptions = [];
        if (in_array('ROLE_PARENT', $userRoles)) {
            $kids = $userRepository->findBy(['parent' => $user, 'type' => 'kid']);

            // Check which kids are subscribed
            foreach ($kids as $kid) {
                $isSubscribed = $subscriptionRepository->isKidSubscribedToCourse(
                    $kid->getId(),
                    $course->getId()
                );
                $kidSubscriptions[$kid->getId()] = $isSubscribed;
            }
        }

        // Get subscription count
        $enrollmentCount = $subscriptionRepository->countActiveSubscriptions($course->getId());

        // Determine which template to use
        $template = (in_array('ROLE_ADMIN', $userRoles) || in_array('ROLE_TEACHER', $userRoles))
            ? 'backoffice/course/detail.html.twig'
            : 'FrontOffice/course/detail.html.twig';

        return $this->render($template, [
            'course' => $course,
            'kids' => $kids,
            'kidSubscriptions' => $kidSubscriptions,
            'enrollmentCount' => $enrollmentCount,
            'userRole' => $this->getMainRole($userRoles),
        ]);
    }

    #[Route('/course/test-gmail', name: 'app_test_gmail')]
    public function testGmail(\Symfony\Component\Mailer\MailerInterface $mailer): Response
    {
        try {
            $email = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('saadliwassieo@gmail.com')
                ->to('saadliwassieo@gmail.com')
                ->subject('✅ Test de configuration Gmail')
                ->html('<h1>Test réussi !</h1><p>Bravo, la configuration email fonctionne !</p>');

            $mailer->send($email);

            return new Response('✅ Email de test envoyé avec succès !');
        } catch (\Exception $e) {
            return new Response('❌ Erreur : ' . $e->getMessage());
        }
    }

}