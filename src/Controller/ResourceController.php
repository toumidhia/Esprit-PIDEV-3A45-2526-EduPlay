<?php
// src/Controller/ResourceController.php

namespace App\Controller;

use App\Entity\Resource;
use App\Form\ResourceType;
use App\Form\ResourceSearchType;
use App\Repository\ResourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\PdfExtractorService;
use App\Service\AgeDetectionService;  // ← AJOUTÉ


final class ResourceController extends AbstractController
{
    // ===============================
    // FRONT OFFICE
    // ===============================

    #[Route('resource', name: 'app_resource')]
    public function index(Request $request, ResourceRepository $resourceRepository): Response
    {
        $searchForm = $this->createForm(ResourceSearchType::class);
        $searchForm->handleRequest($request);

        $resources    = [];
        $activeFilters = [];

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();

            foreach ($data as $key => $value) {
                if (!empty($value) && !in_array($key, ['search', 'reset', 'sortBy'])) {
                    $activeFilters[$key] = $value;
                }
            }

            if ($searchForm->get('reset')->isClicked()) {
                return $this->redirectToRoute('app_resource');
            }

            $resources = $resourceRepository->searchAndSort($data);
        } else {
            $resources = $resourceRepository->findAll();
        }

        return $this->render('FrontOffice/enfant/resource/index.html.twig', [
            'resources'     => $resources,
            'searchForm'    => $searchForm->createView(),
            'activeFilters' => $activeFilters,
        ]);
    }

    // ===============================
    // BACK OFFICE
    // ===============================

    #[Route('admin/resource/', name: 'admin_resource_index', methods: ['GET'])]
    public function adminIndex(Request $request, ResourceRepository $resourceRepository): Response
    {
        $searchForm = $this->createForm(ResourceSearchType::class);
        $searchForm->handleRequest($request);

        $resources    = [];
        $activeFilters = [];

        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();

            foreach ($data as $key => $value) {
                if (!empty($value) && !in_array($key, ['search', 'reset', 'sortBy'])) {
                    $activeFilters[$key] = $value;
                }
            }

            if ($searchForm->get('reset')->isClicked()) {
                return $this->redirectToRoute('admin_resource_index');
            }

            $resources = $resourceRepository->searchAndSort($data);
        } else {
            $resources = $resourceRepository->findAll();
        }

        return $this->render('BackOffice/admin/resource/index.html.twig', [
            'resources'     => $resources,
            'searchForm'    => $searchForm->createView(),
            'activeFilters' => $activeFilters,
        ]);
    }

    #[Route('admin/resource/new', name: 'admin_resource_new', methods: ['GET', 'POST'])]
public function adminNew(
    Request $request,
    EntityManagerInterface $em,
    PdfExtractorService $pdfExtractor,
    AgeDetectionService $ageDetection,
    \App\Repository\BookRequestRepository $bookRequestRepository
): Response {
    $resource = new Resource();
    $form = $this->createForm(ResourceType::class, $resource, [
        'attr'    => ['novalidate' => 'novalidate', 'class' => 'space-y-6'],
        'is_edit' => false,
    ]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $coverImageFile = $form->get('coverImageFile')->getData();
        if ($coverImageFile) {
            $newFilename = uniqid() . '.' . $coverImageFile->guessExtension();
            try {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                $coverImageFile->move($uploadsDir, $newFilename);
                $resource->setCoverImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur upload image : ' . $e->getMessage());
                return $this->redirectToRoute('admin_resource_new');
            }
        }

        $pdfFileFile = $form->get('pdfFileFile')->getData();
        if ($pdfFileFile) {
            $newFilename = uniqid() . '.' . $pdfFileFile->guessExtension();
            try {
                $pdfsDir = $this->getParameter('kernel.project_dir') . '/public/pdfs';
                $pdfFileFile->move($pdfsDir, $newFilename);
                $resource->setPdfFile($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur upload PDF : ' . $e->getMessage());
                return $this->redirectToRoute('admin_resource_new');
            }
        }

        $pdfContent = '';
        if ($resource->getPdfFile()) {
            $pdfContent = $pdfExtractor->extractTextFromPdf($resource->getPdfFile()) ?? '';
        }

        $ageResult = $ageDetection->detectAgeRange(
            title:      $resource->getTitle() ?? '',
            author:     $resource->getAuthor() ?? '',
            summary:    $resource->getSummary() ?? '',
            pdfContent: $pdfContent
        );

        $resource->setMinAge($ageResult['age_min']);
        $resource->setMaxAge($ageResult['age_max']);

        if ($ageResult['success']) {
            $this->addFlash('info', sprintf(
                '🤖 IA : Tranche d\'âge détectée → %d - %d ans. (%s)',
                $ageResult['age_min'],
                $ageResult['age_max'],
                $ageResult['reason']
            ));
        } else {
            $this->addFlash('warning', '⚠️ Détection IA indisponible — âges par défaut appliqués (6-9 ans).');
        }

        $em->persist($resource);
        $em->flush();

        // 🔔 Notifier les enfants qui attendaient ce livre
        $this->notifyWaitingChildren($resource, $em, $bookRequestRepository);

        $this->addFlash('success', 'Ressource ajoutée avec succès.');
        return $this->redirectToRoute('admin_resource_index');
    }

    return $this->render('BackOffice/admin/resource/new.html.twig', [
        'form' => $form->createView(),
    ]);
}





    // ========================= TEST AI (TEMPORAIRE) =========================
#[Route('admin/resource/test-ai', name: 'admin_resource_test_ai')]
public function testAi(AgeDetectionService $ageDetection): Response
{
    $result = $ageDetection->detectAgeRange(
        title: 'Le Petit Prince',
        author: 'Antoine de Saint-Exupéry',
        summary: 'Un aviateur rencontre un petit prince dans le désert',
    );
    dd($result);
}
    // ========================= SHOW =========================
    #[Route('admin/resource/{id}', name: 'admin_resource_show', methods: ['GET'])]
    public function adminShow(Resource $resource): Response
    {
        return $this->render('BackOffice/admin/resource/show.html.twig', [
            'resource' => $resource,
        ]);
    }

    // ========================= EDIT =========================
    #[Route('admin/resource/{id}/edit', name: 'admin_resource_edit', methods: ['GET', 'POST'])]
    public function adminEdit(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em,
        PdfExtractorService $pdfExtractor,
        AgeDetectionService $ageDetection   // ← AJOUTÉ
    ): Response {
        $oldCoverImage = $resource->getCoverImage();
        $oldPdfFile    = $resource->getPdfFile();

        $form = $this->createForm(ResourceType::class, $resource, [
            'attr'    => ['novalidate' => 'novalidate', 'class' => 'space-y-6'],
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── Upload image ──────────────────────────────────────────
            $coverImageFile = $form->get('coverImageFile')->getData();
            if ($coverImageFile) {
                $newFilename = uniqid() . '.' . $coverImageFile->guessExtension();
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $coverImageFile->move($uploadsDir, $newFilename);
                    if ($oldCoverImage && file_exists($uploadsDir . '/' . $oldCoverImage)) {
                        unlink($uploadsDir . '/' . $oldCoverImage);
                    }
                    $resource->setCoverImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload image : ' . $e->getMessage());
                    $resource->setCoverImage($oldCoverImage);
                }
            } else {
                $resource->setCoverImage($oldCoverImage);
            }

            // ── Upload PDF ────────────────────────────────────────────
            $pdfFileFile = $form->get('pdfFileFile')->getData();
            $pdfChanged  = false;

            if ($pdfFileFile) {
                $newFilename = uniqid() . '.' . $pdfFileFile->guessExtension();
                try {
                    $pdfsDir = $this->getParameter('kernel.project_dir') . '/public/pdfs';
                    $pdfFileFile->move($pdfsDir, $newFilename);
                    if ($oldPdfFile && file_exists($pdfsDir . '/' . $oldPdfFile)) {
                        unlink($pdfsDir . '/' . $oldPdfFile);
                    }
                    $resource->setPdfFile($newFilename);
                    $pdfChanged = true;
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur upload PDF : ' . $e->getMessage());
                    $resource->setPdfFile($oldPdfFile);
                }
            } else {
                $resource->setPdfFile($oldPdfFile);
            }

            // ── 🤖 Re-détection IA si titre/résumé ou PDF a changé ───
            // On re-détecte à chaque édition pour garder les âges à jour
            $pdfContent = '';
            if ($resource->getPdfFile()) {
                $pdfContent = $pdfExtractor->extractTextFromPdf($resource->getPdfFile()) ?? '';
            }

            $ageResult = $ageDetection->detectAgeRange(
                title:      $resource->getTitle() ?? '',
                author:     $resource->getAuthor() ?? '',
                summary:    $resource->getSummary() ?? '',
                pdfContent: $pdfContent
            );

            $resource->setMinAge($ageResult['age_min']);
            $resource->setMaxAge($ageResult['age_max']);

            if ($ageResult['success']) {
                $this->addFlash('info', sprintf(
                    '🤖 IA : Tranche d\'âge mise à jour → %d - %d ans. (%s)',
                    $ageResult['age_min'],
                    $ageResult['age_max'],
                    $ageResult['reason']
                ));
            } else {
                $this->addFlash('warning', '⚠️ Détection IA indisponible — âges inchangés.');
            }
            // ──────────────────────────────────────────────────────────

            $em->flush();
            $this->addFlash('success', 'Ressource modifiée avec succès.');
            return $this->redirectToRoute('admin_resource_index');
        }

        return $this->render('BackOffice/admin/resource/edit.html.twig', [
            'form'     => $form->createView(),
            'resource' => $resource,
        ]);
    }

    // ========================= DELETE =========================
    #[Route('admin/resource/{id}/delete', name: 'admin_resource_delete', methods: ['POST'])]
    public function adminDelete(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $resource->getId(), $request->request->get('_token'))) {

            $coverImage  = $resource->getCoverImage();
            $uploadsDir  = $this->getParameter('kernel.project_dir') . '/public/uploads';
            if ($coverImage && file_exists($uploadsDir . '/' . $coverImage)) {
                unlink($uploadsDir . '/' . $coverImage);
            }

            $pdfFile = $resource->getPdfFile();
            $pdfsDir = $this->getParameter('kernel.project_dir') . '/public/pdfs';
            if ($pdfFile && file_exists($pdfsDir . '/' . $pdfFile)) {
                unlink($pdfsDir . '/' . $pdfFile);
            }

            $em->remove($resource);
            $em->flush();

            $this->addFlash('success', 'Ressource supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_resource_index');
    }

    // ========================= FRONT READ =========================
    #[Route('resource/{id}/read', name: 'app_resource_read', methods: ['GET'])]
    public function read(Resource $resource): Response
    {
        return $this->render('FrontOffice/enfant/resource/read.html.twig', [
            'resource' => $resource,
        ]);
    }

    #[Route('FrontOffice/resource/{id}/read-pdf', name: 'app_resource_read_pdf', methods: ['GET'])]
    public function readPdf(Resource $resource, PdfExtractorService $pdfExtractor): Response
    {
        $pdfContent = null;
        $pdfPages   = [];

        if ($resource->getPdfFile()) {
            $pdfContent = $pdfExtractor->extractTextFromPdf($resource->getPdfFile());
            $pdfPages   = $pdfExtractor->extractTextByPages($resource->getPdfFile());
        }

        return $this->render('FrontOffice/enfant/resource/read_pdf.html.twig', [
            'resource'   => $resource,
            'pdfContent' => $pdfContent,
            'pdfPages'   => $pdfPages,
            'hasPdf'     => ($pdfContent !== null && !empty($pdfContent)),
        ]);
    }




    // ========================= RECHERCHE VOCALE =========================
#[Route('admin/resource/voice-search', name: 'admin_resource_voice_search', methods: ['POST'])]
public function voiceSearch(Request $request, ResourceRepository $resourceRepository): Response
{
    $data = json_decode($request->getContent(), true);
    $query = trim($data['query'] ?? '');

    if (empty($query)) {
        return $this->json(['results' => [], 'query' => '']);
    }

    // Recherche dans titre, auteur, résumé
    $resources = $resourceRepository->searchByVoice($query);

    $results = array_map(function($resource) {
        return [
            'id'         => $resource->getId(),
            'title'      => $resource->getTitle(),
            'author'     => $resource->getAuthor(),
            'type'       => $resource->getType(),
            'language'   => $resource->getLanguage(),
            'minAge'     => $resource->getMinAge(),
            'maxAge'     => $resource->getMaxAge(),
            'coverImage' => $resource->getCoverImage(),
            'summary'    => mb_substr($resource->getSummary() ?? '', 0, 100),
            'showUrl'    => $this->generateUrl('admin_resource_show', ['id' => $resource->getId()]),
        ];
    }, $resources);

    return $this->json(['results' => $results, 'query' => $query]);
}

// Pour le frontoffice
#[Route('resource/voice-search', name: 'app_resource_voice_search', methods: ['POST'])]
public function voiceSearchFront(Request $request, ResourceRepository $resourceRepository): Response
{
    $data = json_decode($request->getContent(), true);
    $query = trim($data['query'] ?? '');

    if (empty($query)) {
        return $this->json(['results' => [], 'query' => '']);
    }

    $resources = $resourceRepository->searchByVoice($query);

    $results = array_map(function($resource) {
        return [
            'id'         => $resource->getId(),
            'title'      => $resource->getTitle(),
            'author'     => $resource->getAuthor(),
            'type'       => $resource->getType(),
            'minAge'     => $resource->getMinAge(),
            'maxAge'     => $resource->getMaxAge(),
            'coverImage' => $resource->getCoverImage(),
            'summary'    => mb_substr($resource->getSummary() ?? '', 0, 100),
            'readUrl'    => $this->generateUrl('app_resource_read', ['id' => $resource->getId()]),
        ];
    }, $resources);

    return $this->json(['results' => $results, 'query' => $query]);
}


// ========================= DEMANDE DE LIVRE =========================
#[Route('resource/request-book', name: 'app_resource_request_book', methods: ['POST'])]
public function requestBook(
    Request $request,
    EntityManagerInterface $em,
    ResourceRepository $resourceRepository
): Response {
    // Vérifier que l'enfant est connecté
    $enfant = $this->getUser();
    if (!$enfant || !in_array('ROLE_ENFANT', $enfant->getRoles())) {
        return $this->json(['success' => false, 'message' => 'Connecte-toi pour faire une demande.'], 403);
    }

    $data = json_decode($request->getContent(), true);
    $bookTitle = trim($data['bookTitle'] ?? '');

    if (empty($bookTitle)) {
        return $this->json(['success' => false, 'message' => 'Titre requis']);
    }

    // Vérifier si le livre existe déjà
    $existing = $resourceRepository->searchByVoice($bookTitle);
    if (!empty($existing)) {
        return $this->json([
            'success'       => false,
            'alreadyExists' => true,
            'message'       => 'Ce livre est déjà disponible !',
            'books'         => array_map(fn($r) => [
                'title'   => $r->getTitle(),
                'readUrl' => $this->generateUrl('app_resource_read', ['id' => $r->getId()])
            ], $existing)
        ]);
    }

    // Créer la demande liée à l'enfant connecté
    $bookRequest = new \App\Entity\BookRequest();
    $bookRequest->setBookTitle($bookTitle);
    $bookRequest->setEnfant($enfant);

    $em->persist($bookRequest);
    $em->flush();

    return $this->json([
        'success'   => true,
        'message'   => 'Demande enregistrée ! Tu seras notifié quand "' . $bookTitle . '" sera disponible 📚',
        'enfant'    => $enfant->getFirstName() . ' ' . $enfant->getLastName(),
    ]);
}

// ========================= VÉRIFIER MES NOTIFICATIONS =========================
#[Route('resource/my-notifications', name: 'app_resource_my_notifications', methods: ['GET'])]
public function myNotifications(
    \App\Repository\BookRequestRepository $bookRequestRepository
): Response {
    $enfant = $this->getUser();
    if (!$enfant || !in_array('ROLE_ENFANT', $enfant->getRoles())) {
        return $this->json(['notifications' => []]);
    }

    $notifications = $bookRequestRepository->findNotificationsForEnfant($enfant);

    return $this->json([
        'enfantName'    => $enfant->getFirstName(),
        'notifications' => array_map(fn($n) => [
            'bookTitle'  => $n->getBookTitle(),
            'notifiedAt' => $n->getNotifiedAt()?->format('d/m/Y'),
            'readUrl'    => $n->getResource()
                ? $this->generateUrl('app_resource_read', ['id' => $n->getResource()->getId()])
                : null,
        ], $notifications)
    ]);
}

// ========================= ADMIN — VOIR LES DEMANDES =========================
#[Route('admin/resource/book-requests', name: 'admin_book_requests', methods: ['GET'])]
public function adminBookRequests(\App\Repository\BookRequestRepository $repo): Response
{
    return $this->render('BackOffice/admin/resource/book_requests.html.twig', [
        'pendingRequests' => $repo->findAllPending(),
        'allRequests'     => $repo->findBy([], ['requestedAt' => 'DESC']),
    ]);
}

  // ========================= NOTIFICATION ENFANTS =========================
    private function notifyWaitingChildren(
        \App\Entity\Resource $resource,
        EntityManagerInterface $em,
        \App\Repository\BookRequestRepository $bookRequestRepository
    ): void {
        $pendingRequests = $bookRequestRepository->findPendingByTitle($resource->getTitle());

        foreach ($pendingRequests as $bookRequest) {
            $bookRequest->setIsAvailable(true);
            $bookRequest->setIsNotified(true);
            $bookRequest->setNotifiedAt(new \DateTime());
            $bookRequest->setResource($resource);
            $em->persist($bookRequest);
        }

        if (!empty($pendingRequests)) {
            $em->flush();
            $this->addFlash('info', sprintf(
                '🔔 %d enfant(s) notifié(s) que "%s" est maintenant disponible !',
                count($pendingRequests),
                $resource->getTitle()
            ));
        }
    }


}