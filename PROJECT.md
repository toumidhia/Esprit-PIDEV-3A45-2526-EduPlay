# EduPlay - Educational Platform Project Documentation

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [Architecture](#architecture)
3. [Business Logic (Métiers)](#business-logic-métiers)
4. [Data Model](#data-model)
5. [User Roles & Permissions](#user-roles--permissions)
6. [Routing Strategy](#routing-strategy)
7. [Validation Rules](#validation-rules)
8. [API Implementation](#api-implementation)
9. [Frontend Assets](#frontend-assets)
10. [Development Workflow](#development-workflow)

---

## 🎯 Project Overview

**EduPlay** is a dual-interface educational platform built with Symfony 6+ that manages courses, training sessions (séances), and student subscriptions.

### Key Features
- **Backoffice (Admin/Teacher)**: Bootstrap 5.3 Duralux template for course management, approvals, and scheduling
- **Front Office (Parent/Kid)**: Tailwind CSS interface for browsing and subscribing to courses
- **Course Workflow**: Teachers create courses → Admin approves → Parents subscribe kids
- **Session Management**: Admin schedules training sessions with calendar view

### Technology Stack
- **Backend**: Symfony 6+, PHP 8.1+, Doctrine ORM
- **Backoffice UI**: Bootstrap 5.3, Duralux Admin Template
- **Front Office UI**: Tailwind CSS 3.x
- **Database**: MySQL/MariaDB
- **Calendar**: TUI Calendar for session scheduling
- **Data Tables**: DataTables.js for list management

---

## 🏗️ Architecture

### Dual-Interface Design

The application uses a **role-based routing architecture** to serve two distinct user experiences:

```
┌─────────────────────────────────────────────────────────────┐
│                        EduPlay Platform                      │
├─────────────────────────────┬───────────────────────────────┤
│      BACKOFFICE             │      FRONT OFFICE             │
│   (Bootstrap/Duralux)       │      (Tailwind CSS)           │
├─────────────────────────────┼───────────────────────────────┤
│ Users:                      │ Users:                        │
│ - Administrator             │ - Parent                      │
│ - Teacher                   │ - Kid (Student)               │
├─────────────────────────────┼───────────────────────────────┤
│ Features:                   │ Features:                     │
│ - Dashboard with stats      │ - Browse approved courses     │
│ - Course approval/rejection │ - Subscribe to courses        │
│ - Session scheduling        │ - View subscribed courses     │
│ - Teacher management        │ - Access course materials     │
│ - Calendar view             │ - View training schedule      │
└─────────────────────────────┴───────────────────────────────┘
```

### Directory Structure

```
EduPlay/
├── config/                      # Symfony configuration
│   ├── packages/               # Bundle configurations
│   └── routes/                 # Routing definitions
├── public/
│   ├── backoffice/             # Bootstrap admin assets
│   │   ├── css/
│   │   │   ├── theme.min.css   # Duralux theme
│   │   │   └── bootstrap.min.css
│   │   ├── js/
│   │   │   ├── theme.min.js
│   │   │   └── bootstrap.bundle.min.js
│   │   └── vendors/            # Third-party libraries
│   │       ├── css/            # DataTables, Select2, etc.
│   │       └── js/
│   └── frontend/               # Tailwind assets (front office)
├── src/
│   ├── Controller/
│   │   ├── DashboardController.php    # Admin dashboard
│   │   ├── CourseController.php       # Course CRUD
│   │   └── SeanceController.php       # Session management
│   ├── Entity/
│   │   ├── Course.php          # Course entity
│   │   ├── Seance.php          # Training session entity
│   │   ├── User.php            # User entity
│   │   └── Subscription.php    # Course subscription
│   ├── Form/
│   │   ├── CourseType.php      # Course form
│   │   └── SeanceType.php      # Session form
│   ├── Repository/             # Doctrine repositories
│   └── Service/                # Business logic services
├── templates/
│   ├── backoffice/             # Admin/Teacher templates (Bootstrap)
│   │   ├── base.html.twig      # Master layout
│   │   ├── dashboard.html.twig # Dashboard
│   │   ├── course/             # Course management
│   │   └── seance/             # Session management
│   └── frontend/               # Parent/Kid templates (Tailwind)
│       └── course/             # Course browsing
└── migrations/                 # Database migrations
```

---

## 💼 Business Logic (Métiers)

### Core Business Rules

#### 1. Course Lifecycle
```
[Teacher Creates] → [Pending Status] → [Admin Reviews] → [Approved/Rejected]
```

**Business Flow:**
1. **Teacher** creates a course with details (title, description, level, duration, PDF materials)
2. Course is automatically set to `status: "pending"`
3. **Admin** reviews the course in the dashboard
4. **Admin** can:
   - ✅ **Approve**: Course becomes visible to parents
   - ❌ **Reject**: Course is declined with optional reason
   - 📝 **Request Changes**: Teacher can edit and resubmit

#### 2. Session (Séance) Management
```
[Admin Creates] → [Linked to Course] → [Calendar View] → [Students Enrolled]
```

**Business Flow:**
1. **Admin only** can create training sessions
2. Each session is linked to an approved course
3. Sessions include: date, time, location, status
4. Sessions appear in calendar view for easy scheduling
5. Parents can see sessions for courses their kids are subscribed to

#### 3. Subscription Flow
```
[Parent Browses] → [Views Approved Courses] → [Subscribes Kid] → [Access Granted]
```

**Business Flow:**
1. **Parent** browses approved courses in front office
2. Parent views course details (description, level, materials, sessions)
3. Parent subscribes one or more kids to the course
4. **Kid** can now access course materials and view session schedule

### Service Layer (Métiers Implementation)

The business logic is implemented through Symfony services:

#### CourseService
```php
// src/Service/CourseService.php

class CourseService {
    /**
     * Approve a course (Admin only)
     */
    public function approveCourse(Course $course): void {
        $course->setStatus('accepted');
        // Send notification to teacher
        // Log activity
    }
    
    /**
     * Reject a course (Admin only)
     */
    public function rejectCourse(Course $course, string $reason = null): void {
        $course->setStatus('rejected');
        // Send notification to teacher with reason
        // Log activity
    }
    
    /**
     * Get courses by status for admin dashboard
     */
    public function getCourseStatistics(): array {
        return [
            'total' => $this->courseRepository->count([]),
            'pending' => $this->courseRepository->count(['status' => 'pending']),
            'accepted' => $this->courseRepository->count(['status' => 'accepted']),
            'rejected' => $this->courseRepository->count(['status' => 'rejected']),
        ];
    }
    
    /**
     * Get approved courses for parents
     */
    public function getApprovedCourses(): array {
        return $this->courseRepository->findBy(['status' => 'accepted']);
    }
}
```

#### SubscriptionService
```php
// src/Service/SubscriptionService.php

class SubscriptionService {
    /**
     * Subscribe a kid to a course
     */
    public function subscribeToCourse(User $kid, Course $course, User $parent): Subscription {
        // Validate: course must be approved
        // Validate: kid not already subscribed
        // Create subscription
        // Send confirmation email
        return $subscription;
    }
    
    /**
     * Check if kid has access to course
     */
    public function hasAccess(User $kid, Course $course): bool {
        return $this->subscriptionRepository->findOneBy([
            'kid' => $kid,
            'course' => $course
        ]) !== null;
    }
}
```

#### SeanceService
```php
// src/Service/SeanceService.php

class SeanceService {
    /**
     * Create session for approved course
     */
    public function createSeance(Seance $seance, Course $course): void {
        // Validate: course must be approved
        // Check for scheduling conflicts
        // Create session
        // Notify subscribed parents
    }
    
    /**
     * Get calendar events for TUI Calendar
     */
    public function getCalendarEvents(): array {
        $seances = $this->seanceRepository->findAll();
        return array_map(fn($s) => [
            'id' => $s->getId(),
            'calendarId' => '1',
            'title' => $s->getTitle(),
            'start' => $s->getDate()->format('Y-m-d') . 'T' . $s->getStartTime()->format('H:i:s'),
            'end' => $s->getDate()->format('Y-m-d') . 'T' . $s->getEndTime()->format('H:i:s'),
        ], $seances);
    }
}
```

---

## 📊 Data Model

### Entity Relationships

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│    User     │────────▶│   Course     │◀────────│   Seance    │
│             │  teaches│              │ contains│             │
│ - firstName │         │ - title      │         │ - date      │
│ - lastName  │         │ - duration   │         │ - startTime │
│ - email     │         │ - level      │         │ - endTime   │
│ - type      │         │ - status     │         │ - location  │
│ - role[]    │         │ - pdfFile    │         │             │
└──────┬──────┘         └──────┬───────┘         └─────────────┘
       │                       │
       │                       │
       │                       ▼
       │              ┌─────────────────┐
       └─────────────▶│  Subscription   │
         subscribes   │                 │
                      │ - subscribeDate │
                      │ - status        │
                      └─────────────────┘
```

### Course Entity
```php
/**
 * @ORM\Entity(repositoryClass=CourseRepository::class)
 */
class Course {
    private ?int $id = null;
    
    #[Assert\NotBlank(message: "Title is required")]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    private ?string $description = null;
    
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['beginner', 'intermediate', 'advanced'])]
    private ?string $level = null;
    
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: "/^\d+$/", message: "Duration must be a number")]
    private ?string $durationTraining = null;
    
    private ?string $pdfFile = null; // Path to uploaded PDF
    
    #[Assert\Choice(choices: ['pending', 'accepted', 'rejected'])]
    private string $status = 'pending'; // Default status
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $teacherId = null;
    
    #[ORM\OneToMany(mappedBy: 'courseId', targetEntity: Seance::class)]
    private Collection $seances;
    
    #[ORM\OneToMany(mappedBy: 'course', targetEntity: Subscription::class)]
    private Collection $subscriptions;
}
```

### Seance Entity
```php
/**
 * @ORM\Entity(repositoryClass=SeanceRepository::class)
 */
class Seance {
    private ?int $id = null;
    
    #[Assert\NotBlank]
    private ?string $title = null;
    
    #[Assert\NotNull]
    private ?\DateTimeInterface $date = null;
    
    #[Assert\NotNull]
    private ?\DateTimeInterface $startTime = null;
    
    #[Assert\NotNull]
    private ?\DateTimeInterface $endTime = null;
    
    #[Assert\NotBlank]
    private ?string $location = null;
    
    private string $status = 'scheduled'; // scheduled, completed, cancelled
    
    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'seances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $courseId = null;
}
```

### User Entity
```php
/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User {
    private ?int $id = null;
    
    #[Assert\NotBlank]
    private ?string $firstName = null;
    
    #[Assert\NotBlank]
    private ?string $lastName = null;
    
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;
    
    #[Assert\Choice(choices: ['admin', 'teacher', 'parent', 'kid'])]
    private ?string $type = null;
    
    private array $roles = [];
    
    // Relationships
    #[ORM\OneToMany(mappedBy: 'teacherId', targetEntity: Course::class)]
    private Collection $coursesTeaching;
    
    #[ORM\OneToMany(mappedBy: 'kid', targetEntity: Subscription::class)]
    private Collection $subscriptions;
}
```

### Subscription Entity
```php
/**
 * @ORM\Entity(repositoryClass=SubscriptionRepository::class)
 */
class Subscription {
    private ?int $id = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $parent = null;
    
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $kid = null;
    
    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Course $course = null;
    
    private ?\DateTimeInterface $subscribeDate = null;
    
    private string $status = 'active'; // active, suspended, completed
}
```

---

## 👥 User Roles & Permissions

### Role Matrix

| Feature | Admin | Teacher | Parent | Kid |
|---------|-------|---------|--------|-----|
| **Dashboard** | ✅ Full stats | ❌ | ❌ | ❌ |
| **View All Courses** | ✅ | ❌ Own only | ✅ Approved | ✅ Subscribed |
| **Create Course** | ❌ | ✅ | ❌ | ❌ |
| **Approve/Reject Course** | ✅ | ❌ | ❌ | ❌ |
| **Edit Course** | ❌ | ✅ Own | ❌ | ❌ |
| **Delete Course** | ✅ | ✅ Own | ❌ | ❌ |
| **Create Session** | ✅ | ❌ | ❌ | ❌ |
| **View Calendar** | ✅ | ✅ | ✅ Subscribed | ✅ Subscribed |
| **Subscribe to Course** | ❌ | ❌ | ✅ For kids | ❌ |
| **Access Materials** | ✅ | ✅ Own | ✅ Subscribed | ✅ Subscribed |

### Role Implementation

```php
// config/packages/security.yaml
security:
    access_control:
        # Admin routes
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/dashboard, roles: ROLE_ADMIN }
        
        # Teacher routes
        - { path: ^/teacher/course/new, roles: ROLE_TEACHER }
        - { path: ^/teacher/course/edit, roles: ROLE_TEACHER }
        
        # Parent routes
        - { path: ^/course, roles: [ROLE_PARENT, ROLE_ADMIN, ROLE_TEACHER] }
        - { path: ^/subscribe, roles: ROLE_PARENT }
```

---

## 🛣️ Routing Strategy

### Backoffice Routes (Admin/Teacher)

```yaml
# config/routes.yaml

# Dashboard (Admin only)
app_dashboard:
    path: /dashboard
    controller: App\Controller\DashboardController::index

# Course Management
app_course_index:
    path: /admin/courses
    controller: App\Controller\CourseController::index
    
app_course_new:
    path: /teacher/course/new
    controller: App\Controller\CourseController::new
    
app_course_edit:
    path: /course/{id}/edit
    controller: App\Controller\CourseController::edit
    
app_course_approve:
    path: /admin/course/{id}/approve
    controller: App\Controller\CourseController::approve
    methods: [POST]
    
app_course_reject:
    path: /admin/course/{id}/reject
    controller: App\Controller\CourseController::reject
    methods: [POST]

# Session Management (Admin only)
app_seance_index:
    path: /admin/seances
    controller: App\Controller\SeanceController::index
    
app_seance_calendar:
    path: /admin/seances/calendar
    controller: App\Controller\SeanceController::calendar
    
app_seance_new:
    path: /admin/seance/new
    controller: App\Controller\SeanceController::new
```

### Front Office Routes (Parent/Kid)

```yaml
# Public course browsing
app_course_browse:
    path: /course
    controller: App\Controller\FrontController::browseCourses
    
app_course_detail:
    path: /course/{id}
    controller: App\Controller\FrontController::courseDetail
    
# Subscription
app_course_subscribe:
    path: /course/{id}/subscribe
    controller: App\Controller\SubscriptionController::subscribe
    methods: [POST]
    
app_my_courses:
    path: /my-courses
    controller: App\Controller\FrontController::myCourses
```

### Template Routing in Controllers

```php
// src/Controller/CourseController.php

public function index(): Response {
    $user = $this->getUser();
    
    // Determine template based on role
    if (in_array('ROLE_ADMIN', $user->getRoles()) || 
        in_array('ROLE_TEACHER', $user->getRoles())) {
        // Backoffice template (Bootstrap)
        $template = 'backoffice/course/index.html.twig';
        $courses = $this->courseRepository->findAll(); // Admin sees all
    } else {
        // Front office template (Tailwind)
        $template = 'frontend/course/index.html.twig';
        $courses = $this->courseRepository->findBy(['status' => 'accepted']); // Parents see approved only
    }
    
    return $this->render($template, ['courses' => $courses]);
}
```

---

## ✅ Validation Rules (Contrôle de Saisie)

### Backend Validation (Symfony Assertions)

#### Course Validation
```php
use Symfony\Component\Validator\Constraints as Assert;

class Course {
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $title = null;
    
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $description = null;
    
    #[Assert\Choice(
        choices: ['beginner', 'intermediate', 'advanced'],
        message: "Le niveau doit être : débutant, intermédiaire ou avancé"
    )]
    private ?string $level = null;
    
    #[Assert\Regex(
        pattern: "/^\d+$/",
        message: "La durée doit être un nombre entier"
    )]
    #[Assert\Range(
        min: 1,
        max: 500,
        notInRangeMessage: "La durée doit être entre {{ min }} et {{ max }} heures"
    )]
    private ?string $durationTraining = null;
    
    #[Assert\File(
        maxSize: "10M",
        mimeTypes: ["application/pdf"],
        mimeTypesMessage: "Le fichier doit être au format PDF"
    )]
    private ?UploadedFile $pdfFile = null;
}
```

#### Seance Validation
```php
class Seance {
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    private ?string $title = null;
    
    #[Assert\NotNull(message: "La date est obligatoire")]
    #[Assert\GreaterThan(
        value: "today",
        message: "La date doit être dans le futur"
    )]
    private ?\DateTimeInterface $date = null;
    
    #[Assert\NotNull(message: "L'heure de début est obligatoire")]
    private ?\DateTimeInterface $startTime = null;
    
    #[Assert\NotNull(message: "L'heure de fin est obligatoire")]
    #[Assert\GreaterThan(
        propertyPath: "startTime",
        message: "L'heure de fin doit être après l'heure de début"
    )]
    private ?\DateTimeInterface $endTime = null;
}
```

### Frontend Validation (JavaScript)

#### Course Form Validation
```javascript
// public/backoffice/js/course-validation.js

document.addEventListener('DOMContentLoaded', function() {
    const courseForm = document.querySelector('#course-form');
    
    if (courseForm) {
        courseForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Title validation
            const title = document.querySelector('#course_title');
            if (title.value.trim().length < 3) {
                showError(title, 'Le titre doit contenir au moins 3 caractères');
                isValid = false;
            }
            
            // Description validation
            const description = document.querySelector('#course_description');
            if (description.value.trim().length < 10) {
                showError(description, 'La description doit contenir au moins 10 caractères');
                isValid = false;
            }
            
            // Duration validation
            const duration = document.querySelector('#course_durationTraining');
            if (!/^\d+$/.test(duration.value) || duration.value < 1) {
                showError(duration, 'La durée doit être un nombre positif');
                isValid = false;
            }
            
            // Level validation
            const level = document.querySelector('#course_level');
            if (!level.value) {
                showError(level, 'Veuillez sélectionner un niveau');
                isValid = false;
            }
            
            // PDF file validation
            const pdfFile = document.querySelector('#course_pdfFile');
            if (pdfFile.files.length > 0) {
                const file = pdfFile.files[0];
                if (file.type !== 'application/pdf') {
                    showError(pdfFile, 'Le fichier doit être au format PDF');
                    isValid = false;
                }
                if (file.size > 10 * 1024 * 1024) {
                    showError(pdfFile, 'Le fichier ne doit pas dépasser 10 MB');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});

function showError(element, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    element.classList.add('is-invalid');
    element.parentElement.appendChild(errorDiv);
    
    // Remove error on input change
    element.addEventListener('input', function() {
        element.classList.remove('is-invalid');
        const error = element.parentElement.querySelector('.invalid-feedback');
        if (error) error.remove();
    });
}
```

#### Real-time Validation
```javascript
// Add real-time validation feedback
document.querySelectorAll('input[required], textarea[required], select[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });
});
```

---

## 🔌 API Implementation

### Current Implementation: Server-Side Rendering (SSR)

**EduPlay currently does NOT use a REST API.** All functionality is implemented through traditional Symfony controllers that render Twig templates server-side.

**Flow:**
```
Browser → Route → Controller → Repository → Database
                     ↓
                  Service (Business Logic)
                     ↓
                 Twig Template → HTML Response
```

**Example:**
```php
// src/Controller/CourseController.php
public function index(): Response {
    $courses = $this->courseRepository->findAll();
    
    return $this->render('backoffice/course/index.html.twig', [
        'courses' => $courses
    ]);
}
```

### Future API Implementation (REST)

For mobile app integration or SPA conversion, a REST API can be added:

#### API Structure
```
/api/v1/
├── courses
│   ├── GET    /api/v1/courses              # List courses
│   ├── GET    /api/v1/courses/{id}         # Get course details
│   ├── POST   /api/v1/courses              # Create course (Teacher)
│   ├── PUT    /api/v1/courses/{id}         # Update course (Teacher)
│   ├── DELETE /api/v1/courses/{id}         # Delete course (Teacher/Admin)
│   ├── POST   /api/v1/courses/{id}/approve # Approve course (Admin)
│   └── POST   /api/v1/courses/{id}/reject  # Reject course (Admin)
├── seances
│   ├── GET    /api/v1/seances              # List sessions
│   ├── GET    /api/v1/seances/calendar     # Calendar data
│   ├── POST   /api/v1/seances              # Create session (Admin)
│   └── PUT    /api/v1/seances/{id}         # Update session (Admin)
└── subscriptions
    ├── GET    /api/v1/subscriptions        # My subscriptions
    ├── POST   /api/v1/subscriptions        # Subscribe to course
    └── DELETE /api/v1/subscriptions/{id}   # Unsubscribe
```

#### API Controller Example
```php
// src/Controller/Api/CourseApiController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/courses', name: 'api_course_')]
class CourseApiController extends AbstractController {
    
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(CourseRepository $repo): JsonResponse {
        $courses = $repo->findAll();
        
        return $this->json([
            'success' => true,
            'data' => array_map(fn($c) => [
                'id' => $c->getId(),
                'title' => $c->getTitle(),
                'level' => $c->getLevel(),
                'duration' => $c->getDurationTraining(),
                'status' => $c->getStatus(),
                'teacher' => [
                    'id' => $c->getTeacherId()->getId(),
                    'name' => $c->getTeacherId()->getFirstName() . ' ' . $c->getTeacherId()->getLastName()
                ]
            ], $courses)
        ]);
    }
    
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Course $course): JsonResponse {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'level' => $course->getLevel(),
                'duration' => $course->getDurationTraining(),
                'status' => $course->getStatus(),
                'pdfFile' => $course->getPdfFile(),
                'teacher' => [
                    'id' => $course->getTeacherId()->getId(),
                    'firstName' => $course->getTeacherId()->getFirstName(),
                    'lastName' => $course->getTeacherId()->getLastName(),
                    'email' => $course->getTeacherId()->getEmail()
                ],
                'seances' => array_map(fn($s) => [
                    'id' => $s->getId(),
                    'title' => $s->getTitle(),
                    'date' => $s->getDate()->format('Y-m-d'),
                    'startTime' => $s->getStartTime()->format('H:i'),
                    'endTime' => $s->getEndTime()->format('H:i'),
                    'location' => $s->getLocation()
                ], $course->getSeances()->toArray())
            ]
        ]);
    }
    
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_TEACHER')]
    public function create(Request $request): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Validation
        // Create course
        // Return response
        
        return $this->json([
            'success' => true,
            'message' => 'Course created successfully',
            'data' => ['id' => $course->getId()]
        ], 201);
    }
    
    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Course $course, CourseService $service): JsonResponse {
        $service->approveCourse($course);
        
        return $this->json([
            'success' => true,
            'message' => 'Course approved successfully'
        ]);
    }
}
```

#### API Authentication (JWT)

For API security, implement JWT (JSON Web Tokens):

```bash
composer require lexik/jwt-authentication-bundle
```

```yaml
# config/packages/security.yaml
security:
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
```

**Login Endpoint:**
```php
// POST /api/login
{
    "email": "teacher@eduplay.com",
    "password": "password123"
}

// Response:
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "def50200..."
}
```

---

## 🎨 Frontend Assets

### Backoffice Assets (Bootstrap/Duralux)

#### CSS Files
```html
<!-- Base Template: templates/backoffice/base.html.twig -->

<!-- Bootstrap Core -->
<link rel="stylesheet" href="{{ asset('backoffice/css/bootstrap.min.css') }}">

<!-- Vendors -->
<link rel="stylesheet" href="{{ asset('backoffice/vendors/css/vendors.min.css') }}">
<link rel="stylesheet" href="{{ asset('backoffice/vendors/css/feather.min.css') }}">
<link rel="stylesheet" href="{{ asset('backoffice/vendors/css/fontawesome.min.css') }}">

<!-- Theme -->
<link rel="stylesheet" href="{{ asset('backoffice/css/theme.min.css') }}">

<!-- Page-specific vendors -->
{% block stylesheets %}
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('backoffice/vendors/css/dataTables.bs5.min.css') }}">
    
    <!-- TUI Calendar -->
    <link rel="stylesheet" href="{{ asset('backoffice/vendors/css/tui-calendar.min.css') }}">
    
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('backoffice/vendors/css/select2.min.css') }}">
{% endblock %}
```

#### JavaScript Files
```html
<!-- Vendors -->
<script src="{{ asset('backoffice/vendors/js/vendors.min.js') }}"></script>

<!-- Bootstrap -->
<script src="{{ asset('backoffice/js/bootstrap.bundle.min.js') }}"></script>

<!-- Theme -->
<script src="{{ asset('backoffice/js/theme.min.js') }}"></script>

<!-- Page-specific -->
{% block javascripts %}
    <!-- DataTables -->
    <script src="{{ asset('backoffice/vendors/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('backoffice/vendors/js/dataTables.bs5.min.js') }}"></script>
    
    <!-- Course validation -->
    <script src="{{ asset('backoffice/js/course-validation.js') }}"></script>
{% endblock %}
```

### Front Office Assets (Tailwind)

```html
<!-- Base Template: templates/frontend/base.html.twig -->

<!-- Tailwind CSS -->
<link rel="stylesheet" href="{{ asset('frontend/css/tailwind.min.css') }}">

<!-- Custom Styles -->
<link rel="stylesheet" href="{{ asset('frontend/css/custom.css') }}">
```

### Asset Organization

```
public/
├── backoffice/                 # Admin/Teacher interface
│   ├── css/
│   │   ├── bootstrap.min.css   # Bootstrap 5.3
│   │   └── theme.min.css       # Duralux theme
│   ├── js/
│   │   ├── bootstrap.bundle.min.js
│   │   ├── theme.min.js
│   │   └── course-validation.js
│   ├── vendors/
│   │   ├── css/
│   │   │   ├── dataTables.bs5.min.css
│   │   │   ├── tui-calendar.min.css
│   │   │   ├── select2.min.css
│   │   │   ├── feather.min.css
│   │   │   └── fontawesome.min.css
│   │   └── js/
│   │       ├── vendors.min.js  # jQuery + utilities
│   │       ├── dataTables.min.js
│   │       ├── tui-calendar.min.js
│   │       └── select2.min.js
│   └── images/
│       └── favicon.ico
└── frontend/                   # Parent/Kid interface
    ├── css/
    │   ├── tailwind.min.css
    │   └── custom.css
    ├── js/
    │   └── app.js
    └── images/
```

---

## 🛠️ Development Workflow

### 1. Database Migrations

```bash
# Create migration for new changes
php bin/console make:migration

# Review migration file in migrations/
# Apply migration
php bin/console doctrine:migrations:migrate
```

### 2. Creating a New Entity

```bash
# Generate entity
php bin/console make:entity

# Generate repository
# Add validation constraints to entity properties
# Create migration
php bin/console make:migration

# Apply migration
php bin/console doctrine:migrations:migrate
```

### 3. Creating a Controller

```bash
# Generate controller
php bin/console make:controller CourseController

# Add routes in config/routes.yaml or use attributes
# Implement business logic in methods
# Create corresponding Twig templates
```

### 4. Creating Forms

```bash
# Generate form type
php bin/console make:form CourseType

# Select entity: Course
# Customize field types and options in generated form class
```

### 5. Testing Changes

```bash
# Clear cache
php bin/console cache:clear

# Run Symfony server
symfony server:start

# Or use PHP built-in server
php -S localhost:8000 -t public

# Access backoffice: http://localhost:8000/dashboard?role=admin
# Access front office: http://localhost:8000/course?role=parent
```

### 6. Debugging

```bash
# Check routes
php bin/console debug:router

# Check services
php bin/console debug:container

# Check Doctrine mapping
php bin/console doctrine:mapping:info

# Validate schema
php bin/console doctrine:schema:validate
```

---

## 📝 Key Implementation Notes

### Business Rule Enforcement

1. **Course Creation**: Only teachers can create courses (status automatically set to "pending")
2. **Course Approval**: Only admins can approve/reject courses
3. **Session Creation**: Only admins can create training sessions
4. **Subscription**: Only parents can subscribe kids to approved courses
5. **Access Control**: Kids can only access courses they are subscribed to

### Security Considerations

1. **Role-based Access Control**: Implement proper `@IsGranted()` annotations on controller methods
2. **CSRF Protection**: Enabled by default in Symfony forms
3. **File Upload Security**: Validate file types and sizes for PDF uploads
4. **SQL Injection Prevention**: Use Doctrine ORM with parameterized queries
5. **XSS Protection**: Twig auto-escapes output by default

### Performance Optimizations

1. **Lazy Loading**: Use Doctrine lazy loading for relationships
2. **Query Optimization**: Use DQL/QueryBuilder for complex queries
3. **Asset Minification**: Use minified CSS/JS in production
4. **Caching**: Enable Symfony cache in production (`APP_ENV=prod`)

---

## 🚀 Deployment Checklist

- [ ] Set `APP_ENV=prod` in `.env.production`
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Clear and warm up cache: `php bin/console cache:clear --env=prod`
- [ ] Run database migrations: `php bin/console doctrine:migrations:migrate --no-interaction`
- [ ] Configure web server (Apache/Nginx) to point to `public/` directory
- [ ] Set proper file permissions for `var/cache`, `var/log`, `public/uploads`
- [ ] Configure HTTPS/SSL certificate
- [ ] Set up backup strategy for database and uploaded files
- [ ] Configure error logging and monitoring

---

## 📞 Support & Maintenance

### Common Issues

**Problem:** Course not appearing in front office
- **Solution:** Check course status is "accepted", verify role-based filtering

**Problem:** PDF upload fails
- **Solution:** Check `upload_max_filesize` and `post_max_size` in php.ini

**Problem:** Calendar not displaying sessions
- **Solution:** Verify seance dates are properly formatted, check JavaScript console for errors

### Future Enhancements

1. **Email Notifications**: Send emails on course approval, subscription confirmation
2. **Payment Integration**: Add payment gateway for course subscriptions
3. **Progress Tracking**: Track student progress through courses
4. **Certificates**: Generate certificates upon course completion
5. **Mobile App**: Build native mobile apps using REST API

---

**Version:** 1.0.0  
**Last Updated:** February 2026  
**Framework:** Symfony 6+  
**License:** Proprietary
