<?php
// src/Controller/ResourceController.php

namespace App\Controller;

use App\Entity\Resource;
use App\Form\ResourceType;
use App\Form\ResourceSearchType;
use App\Repository\ResourceRepository;
use App\Repository\BookRequestRepository;
use App\Repository\LibraryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Service\PdfExtractorService;
use App\Service\AgeDetectionService;
use App\Service\BookRecommendationChatbotService;

final class ResourceController extends AbstractController
{
    // ===============================
    // FRONT OFFICE — routes fixes (AVANT les routes avec {id})
    // ===============================

    // ========================= LISTE FRONT =========================
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

    // ========================= RECHERCHE VOCALE FRONT =========================
    // Route fixe — doit être AVANT resource/{id}
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

    // ========================= CHATBOT RECOMMANDATION LIVRES =========================
    #[Route('resource/chatbot', name: 'app_resource_chatbot', methods: ['POST'])]
    public function chatbot(
        Request $request,
        ResourceRepository $resourceRepository,
        LibraryRepository $libraryRepository,
        BookRecommendationChatbotService $chatbot
    ): Response {
        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');
        $libraryId = (int) ($data['libraryId'] ?? 0);

        if (empty($message)) {
            return $this->json(['success' => false, 'response' => 'Dis-moi ton âge et/ou tes préférences ! Ex: "J\'ai 8 ans et j\'aime les animaux" 😊']);
        }

        $age = $this->extractAgeFromMessage($message);

        $library = $libraryId > 0 ? $libraryRepository->find($libraryId) : null;
        $resources = $library
            ? $resourceRepository->findBy(['libraryId' => $library], ['title' => 'ASC'])
            : $resourceRepository->findBy([], ['title' => 'ASC']);

        // Filtrer par tranche d'âge : minAge <= âge <= maxAge (âge = min, max ou dans l'intervalle)
        if ($age !== null && $age >= 1) {
            $resources = array_filter($resources, fn($r) =>
                $r->getMinAge() <= $age && $age <= $r->getMaxAge()
            );
        }

        $books = array_map(fn($r) => [
            'title'   => $r->getTitle(),
            'author'  => $r->getAuthor(),
            'minAge'  => $r->getMinAge(),
            'maxAge'  => $r->getMaxAge(),
            'type'    => $r->getType(),
            'summary' => $r->getSummary() ?? '',
        ], array_values($resources));

        $response = $chatbot->getRecommendation($message, $books, $age);

        if ($response === null) {
            return $this->json([
                'success'  => false,
                'response' => 'Oups, je n\'arrive pas à répondre pour l\'instant. Réessaie plus tard ! 🤗',
            ]);
        }

        return $this->json(['success' => true, 'response' => $response]);
    }

    // ========================= DEMANDE DE LIVRE =========================
    // L'enfant connecté demande un livre introuvable
    // Route fixe — doit être AVANT resource/{id}
    #[Route('resource/request-book', name: 'app_resource_request_book', methods: ['POST'])]
    public function requestBook(
        Request $request,
        EntityManagerInterface $em,
        ResourceRepository $resourceRepository
    ): Response {
        // Vérifier que l'utilisateur est connecté
        $enfant = $this->getUser();
        if (!$enfant) {
            return $this->json(['success' => false, 'message' => 'Connecte-toi pour faire une demande.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $bookTitle = trim($data['bookTitle'] ?? '');

        if (empty($bookTitle)) {
            return $this->json(['success' => false, 'message' => 'Titre requis']);
        }

        // Vérifier si le livre existe déjà avant de créer une demande
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

        // Créer la demande liée à l'enfant connecté (pas besoin de saisir son nom)
        $bookRequest = new \App\Entity\BookRequest();
        $bookRequest->setBookTitle($bookTitle);
        $bookRequest->setEnfant($enfant);

        $em->persist($bookRequest);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Demande enregistrée ! Tu seras notifié quand "' . $bookTitle . '" sera disponible 📚',
        ]);
    }

    // ========================= MES NOTIFICATIONS =========================
    // Retourne les notifications de l'enfant connecté (livres demandés maintenant disponibles)
    // Route fixe — doit être AVANT resource/{id}
    #[Route('resource/my-notifications', name: 'app_resource_my_notifications', methods: ['GET'])]
    public function myNotifications(BookRequestRepository $bookRequestRepository): Response
    {
        $enfant = $this->getUser();

        // Si non connecté → retourner tableau vide (pas d'erreur)
        if (!$enfant) {
            return $this->json(['notifications' => []]);
        }

        $notifications = $bookRequestRepository->findNotificationsForEnfant($enfant);

        return $this->json([
            'enfantName'    => $enfant->getFirstName(),
            'notifications' => array_map(fn($n) => [
                'bookTitle'  => $n->getBookTitle(),
                'notifiedAt' => $n->getNotifiedAt()?->format('d/m/Y'),
                // readUrl disponible si la ressource a été liée lors de la notification
                'readUrl'    => $n->getResource()
                    ? $this->generateUrl('app_resource_read', ['id' => $n->getResource()->getId()])
                    : null,
            ], $notifications)
        ]);
    }


// Ajouter cette route dans ResourceController.php
// AVANT les routes avec {id} — donc avant admin/resource/{id}

// ========================= FRONT — GÉNÉRATION BD =========================
// Génère une bande dessinée depuis le PDF du livre
// Route fixe — doit être AVANT resource/{id}
// ========================= FRONT — PAGE BD (affichage) =========================
// Affiche la page BD avec écran de chargement
#[Route('resource/{id}/comic', name: 'app_resource_comic', methods: ['GET'], requirements: ['id' => '\d+'])]
public function comic(Resource $resource): Response
{
    return $this->render('FrontOffice/enfant/resource/comic.html.twig', [
        'resource' => $resource,
    ]);
}

#[Route('resource/{id}/comic/generate', name: 'app_resource_comic_generate', methods: ['GET'], requirements: ['id' => '\d+'])]
public function comicGenerate(
    Resource $resource,
    PdfExtractorService $pdfExtractor,
    \App\Service\ComicGeneratorService $comicGenerator
): Response {
    set_time_limit(120);

    $pdfText = '';
    if ($resource->getPdfFile()) {
        $pdfText = $pdfExtractor->extractTextFromPdf($resource->getPdfFile()) ?? '';
    }

    $contentToUse = !empty($pdfText)
        ? $pdfText
        : ($resource->getSummary()
            ?? 'Livre : ' . $resource->getTitle() . ' par ' . $resource->getAuthor());

    try {
        $comic = $comicGenerator->generateComic(
            resourceId: $resource->getId(),
            pdfText:    $contentToUse,
            minAge:     $resource->getMinAge(),
            maxAge:     $resource->getMaxAge()
        );

        return $this->json(['success' => true, 'comic' => $comic]);

    } catch (\Exception $e) {
        return $this->json(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ========================= TRADUCTION MYMEMORY =========================
// Double-clic sur un mot → traduction gratuite sans clé API
// ========================= TRADUCTION MYMEMORY =========================
// Double-clic sur un mot → traduction gratuite sans clé API
#[Route('resource/translate', name: 'app_resource_translate', methods: ['POST'])]
public function translate(Request $request): Response
{
    $data     = json_decode($request->getContent(), true);
    $word     = trim($data['word'] ?? '');
    $fromLang = $data['from'] ?? 'fr';
    $toLang   = $data['to'] ?? 'ar-SA';

    if (empty($word)) {
        return $this->json(['error' => 'Mot vide']);
    }

    // Normaliser les codes de langue
    // ar → ar-SA (arabe standard moderne, الفصحى)
    $langMap = [
        'ar'    => 'ar-SA',
        'ar-TN' => 'ar-SA',
        'fr'    => 'fr-FR',
        'en'    => 'en-US',
        'es'    => 'es-ES',
        'de'    => 'de-DE',
        'it'    => 'it-IT',
    ];

    $fromLang = $langMap[$fromLang] ?? $fromLang;
    $toLang   = $langMap[$toLang]   ?? $toLang;

    // MyMemory — gratuit, sans clé, sans inscription
    $url = "https://api.mymemory.translated.net/get?q="
         . urlencode($word)
         . "&langpair={$fromLang}|{$toLang}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return $this->json(['error' => 'Traduction indisponible']);
    }

    $result      = json_decode($response, true);
    $translation = $result['responseData']['translatedText'] ?? 'Traduction indisponible';

    // Vérifier que la traduction n'est pas une erreur MyMemory
    if (str_contains(strtoupper($translation), 'QUERY LENGTH')) {
        return $this->json(['error' => 'Mot trop long pour la traduction']);
    }

    return $this->json([
        'word'        => $word,
        'translation' => $translation,
        'from'        => $fromLang,
        'to'          => $toLang,
    ]);
}


    // ========================= FRONT READ — routes avec {id} =========================
    #[Route('resource/{id}/read', name: 'app_resource_read', methods: ['GET'])]
    public function read(Resource $resource): Response
    {
        return $this->render('FrontOffice/enfant/resource/read.html.twig', [
            'resource' => $resource,
        ]);
    }

    // ========================= FRONT READ PDF =========================
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

    // ===============================
    // BACK OFFICE — routes fixes (AVANT les routes avec {id})
    // ===============================

    // ========================= LISTE ADMIN =========================
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

    // ========================= CREATE =========================
    // Crée une nouvelle ressource avec détection IA de la tranche d'âge
    // Supporte le pré-remplissage du titre depuis une demande enfant (?prefill=titre)
    #[Route('admin/resource/new', name: 'admin_resource_new', methods: ['GET', 'POST'])]
    public function adminNew(
        Request $request,
        EntityManagerInterface $em,
        PdfExtractorService $pdfExtractor,
        AgeDetectionService $ageDetection,
        BookRequestRepository $bookRequestRepository
    ): Response {
        $resource = new Resource();

        // ✅ Pré-remplir le titre depuis une demande enfant (bouton "+ Ajouter ce livre")
        $prefillTitle = $request->query->get('prefill');
        if ($prefillTitle) {
            $resource->setTitle($prefillTitle);
        }

        $form = $this->createForm(ResourceType::class, $resource, [
            'attr'    => ['novalidate' => 'novalidate', 'class' => 'space-y-6'],
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── 🤖 Détection IA de la tranche d'âge ──────────────────
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

            // Appliquer les âges détectés par l'IA
            $resource->setMinAge($ageResult['age_min']);
            $resource->setMaxAge($ageResult['age_max']);

            // Flash info selon le succès de la détection
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
            // ──────────────────────────────────────────────────────────

            $em->persist($resource);
            $em->flush();

            // 🔔 Notifier automatiquement les enfants qui attendaient ce livre
            $this->notifyWaitingChildren($resource, $em, $bookRequestRepository);

            $this->addFlash('success', 'Ressource ajoutée avec succès.');
            return $this->redirectToRoute('admin_resource_index');
        }

        return $this->render('BackOffice/admin/resource/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ========================= TEST AI (TEMPORAIRE) =========================
    // Route de test pour vérifier que la détection IA fonctionne
    // À supprimer en production
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

    // ========================= RECHERCHE VOCALE ADMIN =========================
    // Route fixe — doit être AVANT admin/resource/{id}
    #[Route('admin/resource/voice-search', name: 'admin_resource_voice_search', methods: ['POST'])]
    public function voiceSearch(Request $request, ResourceRepository $resourceRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        $query = trim($data['query'] ?? '');

        if (empty($query)) {
            return $this->json(['results' => [], 'query' => '']);
        }

        // Recherche dans titre, auteur, résumé, type, langue
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

    // ========================= ADMIN — VOIR LES DEMANDES =========================
    // Liste tous les livres demandés par les enfants et non encore disponibles
    // Route fixe — doit être AVANT admin/resource/{id}
    #[Route('admin/resource/book-requests', name: 'admin_book_requests', methods: ['GET'])]
    public function adminBookRequests(BookRequestRepository $repo): Response
    {
        return $this->render('BackOffice/admin/resource/book_requests.html.twig', [
            'pendingRequests' => $repo->findAllPending(),                        // En attente uniquement
            'allRequests'     => $repo->findBy([], ['requestedAt' => 'DESC']),   // Historique complet
        ]);
    }

    // ===============================
    // BACK OFFICE — routes avec {id} EN DERNIER
    // (sinon Symfony intercepte /new, /book-requests etc. comme des {id})
    // ===============================

    // ========================= SHOW =========================
    #[Route('admin/resource/{id}', name: 'admin_resource_show', methods: ['GET'])]
    public function adminShow(Resource $resource): Response
    {
        return $this->render('BackOffice/admin/resource/show.html.twig', [
            'resource' => $resource,
        ]);
    }

    // ========================= EDIT =========================
    // Modifie une ressource existante avec re-détection IA de la tranche d'âge
    #[Route('admin/resource/{id}/edit', name: 'admin_resource_edit', methods: ['GET', 'POST'])]
    public function adminEdit(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em,
        PdfExtractorService $pdfExtractor,
        AgeDetectionService $ageDetection
    ): Response {
        $oldCoverImage = $resource->getCoverImage();
        $oldPdfFile    = $resource->getPdfFile();

        $form = $this->createForm(ResourceType::class, $resource, [
            'attr'    => ['novalidate' => 'novalidate', 'class' => 'space-y-6'],
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ── 🤖 Re-détection IA à chaque édition pour garder les âges à jour ───
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
    // Supprime une ressource et ses fichiers associés (image + PDF)
    #[Route('admin/resource/{id}/delete', name: 'admin_resource_delete', methods: ['POST'])]
    public function adminDelete(
        Request $request,
        Resource $resource,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $resource->getId(), $request->request->get('_token'))) {
            $em->remove($resource);
            $em->flush();

            $this->addFlash('success', 'Ressource supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_resource_index');
    }

    // ========================= NOTIFICATION ENFANTS =========================
    // Méthode privée appelée après l'ajout d'une ressource
    // Cherche toutes les demandes en attente correspondant au titre
    // et notifie automatiquement les enfants concernés
    private function notifyWaitingChildren(
        \App\Entity\Resource $resource,
        EntityManagerInterface $em,
        BookRequestRepository $bookRequestRepository
    ): void {
        // Chercher les demandes non notifiées correspondant au titre du livre ajouté
        $pendingRequests = $bookRequestRepository->findPendingByTitle($resource->getTitle());

        foreach ($pendingRequests as $bookRequest) {
            $bookRequest->setIsAvailable(true);
            $bookRequest->setIsNotified(true);
            $bookRequest->setNotifiedAt(new \DateTime());
            // Lier la ressource à la demande pour que l'enfant puisse cliquer "Lire"
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

    /**
     * Extrait l'âge du message (ex: "J'ai 8 ans", "8 ans", "livres pour 10 ans")
     */
    private function extractAgeFromMessage(string $message): ?int
    {
        $message = mb_strtolower($message);
        if (preg_match('/(?:j\'?ai\s+|j\'?a\s+)?(\d{1,2})\s*ans?/u', $message, $m)) {
            $age = (int) $m[1];
            return ($age >= 0 && $age <= 99) ? $age : null;
        }
        if (preg_match('/(?:pour|de)\s+(\d{1,2})\s*ans?/u', $message, $m)) {
            $age = (int) $m[1];
            return ($age >= 0 && $age <= 99) ? $age : null;
        }
        if (preg_match('/\b(\d{1,2})\s*ans?\b/u', $message, $m)) {
            $age = (int) $m[1];
            return ($age >= 0 && $age <= 99) ? $age : null;
        }
        return null;
    }
}