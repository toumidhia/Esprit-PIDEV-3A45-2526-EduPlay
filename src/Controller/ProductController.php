<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/product')]
class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
    ) {
    }

    #[Route('', name: 'app_back_product_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search');
        $availability = $request->query->get('availability');
        if ($availability !== null && $availability !== '') {
            $availability = $availability === '1';
        }
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $products = $this->productRepository->searchFilterSort($search, $availability, $sortBy, $sortOrder);
        $stats = [
            'total' => count($this->productRepository->findAll()),
            'available' => $this->productRepository->countAvailable(),
            'unavailable' => $this->productRepository->countUnavailable(),
        ];

        return $this->render('BackOffice/admin/product/index.html.twig', [
            'products' => $products,
            'stats' => $stats,
            'search' => $search,
            'availability_filter' => $availability,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }

    #[Route('/new', name: 'app_back_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // handle uploaded picture
            /** @var UploadedFile|null $pictureFile */
            $pictureFile = $form->get('pictureFile')->getData();
            if ($pictureFile instanceof UploadedFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                try {
                    $extension = $pictureFile->guessExtension();
                } catch (\LogicException $e) {
                    $extension = $pictureFile->getClientOriginalExtension() ?: pathinfo($pictureFile->getClientOriginalName(), PATHINFO_EXTENSION);
                }
                $extension = $extension ? '.' . ltrim($extension, '.') : '';
                $newName = uniqid('prod_') . $extension;
                $pictureFile->move($uploadsDir, $newName);
                $product->setPicture($newName);
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();
            $this->addFlash('success', 'Le produit a été créé avec succès.');

            return $this->redirectToRoute('app_back_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('BackOffice/admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('BackOffice/admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_back_product_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // handle uploaded picture
            /** @var UploadedFile|null $pictureFile */
            $pictureFile = $form->get('pictureFile')->getData();
            if ($pictureFile instanceof UploadedFile) {
                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                try {
                    $extension = $pictureFile->guessExtension();
                } catch (\LogicException $e) {
                    $extension = $pictureFile->getClientOriginalExtension() ?: pathinfo($pictureFile->getClientOriginalName(), PATHINFO_EXTENSION);
                }
                $extension = $extension ? '.' . ltrim($extension, '.') : '';
                $newName = uniqid('prod_') . $extension;
                $pictureFile->move($uploadsDir, $newName);
                $product->setPicture($newName);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Le produit a été modifié avec succès.');

            return $this->redirectToRoute('app_back_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('BackOffice/admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_back_product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Product $product): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $token)) {
            try {
                $this->entityManager->remove($product);
                $this->entityManager->flush();
                $this->addFlash('success', 'Le produit a été supprimé.');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', 'Impossible de supprimer ce produit car il est lié à des commandes existantes. Veuillez d\'abord supprimer les commandes associées.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du produit.');
            }
        }

        return $this->redirectToRoute('app_back_product_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'app_back_product_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        $search = $request->query->get('search');
        $availability = $request->query->get('availability');
        if ($availability !== null && $availability !== '') {
            $availability = $availability === '1';
        }
        $sortBy = $request->query->get('sort', 'id');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $products = $this->productRepository->searchFilterSort($search, $availability, $sortBy, $sortOrder);

        $html = $this->renderView('BackOffice/admin/product/export_pdf.html.twig', ['products' => $products]);
        $pdf = $this->getPdfFromHtml($html);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="produits-eduplay-' . date('Y-m-d') . '.pdf"',
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
