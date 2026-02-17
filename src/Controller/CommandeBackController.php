<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commande')]
class CommandeBackController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CommandeRepository $commandeRepository,
        private UserRepository $userRepository,
        private ProductRepository $productRepository,
    ) {
    }

    #[Route('', name: 'app_back_commande_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search');
        $dateFrom = $request->query->get('date_from') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('date_from')) : null;
        $dateTo = $request->query->get('date_to') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('date_to')) : null;
        $userId = $request->query->getInt('user_id', 0) ?: null;
        $productId = $request->query->getInt('product_id', 0) ?: null;
        $sortBy = $request->query->get('sort', 'dateCommande');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $commandes = $this->commandeRepository->searchFilterSort($search, $dateFrom, $dateTo, $userId, $productId, $sortBy, $sortOrder);
        $stats = [
            'total' => $this->commandeRepository->countCommandes(),
            'totalAmount' => $this->commandeRepository->totalAmountSum(),
        ];
        $users = $this->userRepository->findBy([], ['lastName' => 'ASC']);
        $products = $this->productRepository->findBy([], ['name' => 'ASC']);

        return $this->render('BackOffice/admin/commande/index.html.twig', [
            'commandes' => $commandes,
            'stats' => $stats,
            'users' => $users,
            'products' => $products,
            'search' => $search,
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'user_id' => $userId,
            'product_id' => $productId,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'app_back_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $commande = new Commande();
        $commande->setDateCommande(new \DateTime());
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // The form maps `user` and `product` directly to the entity relations.
            $this->entityManager->persist($commande);
            $this->entityManager->flush();
            $this->addFlash('success', 'La commande a été créée avec succès.');

            return $this->redirectToRoute('app_back_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('BackOffice/admin/commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_back_commande_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('BackOffice/admin/commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_commande_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande): Response
    {
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Form maps relations directly; just flush.
            $this->entityManager->flush();
            $this->addFlash('success', 'La commande a été modifiée avec succès.');

            return $this->redirectToRoute('app_back_commande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('BackOffice/admin/commande/edit.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_back_commande_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Commande $commande): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $commande->getId(), $token)) {
            $this->entityManager->remove($commande);
            $this->entityManager->flush();
            $this->addFlash('success', 'La commande a été supprimée.');
        }

        return $this->redirectToRoute('app_back_commande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'app_back_commande_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        $search = $request->query->get('search');
        $dateFrom = $request->query->get('date_from') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('date_from')) : null;
        $dateTo = $request->query->get('date_to') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('date_to')) : null;
        $userId = $request->query->getInt('user_id', 0) ?: null;
        $productId = $request->query->getInt('product_id', 0) ?: null;
        $sortBy = $request->query->get('sort', 'dateCommande');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $commandes = $this->commandeRepository->searchFilterSort($search, $dateFrom, $dateTo, $userId, $productId, $sortBy, $sortOrder);

        $html = $this->renderView('BackOffice/admin/commande/export_pdf.html.twig', ['commandes' => $commandes]);
        $pdf = $this->getPdfFromHtml($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="commandes-eduplay-' . date('Y-m-d') . '.pdf"',
        ]);
    }

    private function getPdfFromHtml(string $html): string
    {
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->output();
    }
}
