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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class ResourceController extends AbstractController
{
    // ===============================
    // FRONT OFFICE - Afficher toutes les ressources
    // ===============================
    
    #[Route('FrontOffice/resource', name: 'app_resource')]
    public function index(Request $request, ResourceRepository $resourceRepository): Response
    {
        $searchForm = $this->createForm(ResourceSearchType::class);
        $searchForm->handleRequest($request);
        
        $resources = [];
        $activeFilters = [];
        
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            
            // Récupérer les filtres actifs pour l'affichage
            foreach ($data as $key => $value) {
                if (!empty($value) && !in_array($key, ['search', 'reset', 'sortBy'])) {
                    $activeFilters[$key] = $value;
                }
            }
            
            // Si le bouton reset est cliqué
            if ($searchForm->get('reset')->isClicked()) {
                return $this->redirectToRoute('app_resource');
            }
            
            // Recherche avec les critères
            $resources = $resourceRepository->searchAndSort($data);
        } else {
            $resources = $resourceRepository->findAll();
        }
        
        return $this->render('FrontOffice/resource/index.html.twig', [
            'resources' => $resources,
            'searchForm' => $searchForm->createView(),
            'activeFilters' => $activeFilters,
        ]);
    }

    // ===============================
    // BACK OFFICE (Admin) - CRUD Operations
    // ===============================
    
    // ========================= LIST WITH SEARCH =========================
    #[Route('BackOffice/admin/resource/', name: 'admin_resource_index', methods: ['GET'])]
    public function adminIndex(Request $request, ResourceRepository $resourceRepository): Response
    {
        $searchForm = $this->createForm(ResourceSearchType::class);
        $searchForm->handleRequest($request);
        
        $resources = [];
        $activeFilters = [];
        
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            
            // Récupérer les filtres actifs pour l'affichage
            foreach ($data as $key => $value) {
                if (!empty($value) && !in_array($key, ['search', 'reset', 'sortBy'])) {
                    $activeFilters[$key] = $value;
                }
            }
            
            // Si le bouton reset est cliqué
            if ($searchForm->get('reset')->isClicked()) {
                return $this->redirectToRoute('admin_resource_index');
            }
            
            // Recherche avec les critères
            $resources = $resourceRepository->searchAndSort($data);
        } else {
            $resources = $resourceRepository->findAll();
        }
        
        return $this->render('BackOffice/admin/resource/index.html.twig', [
            'resources' => $resources,
            'searchForm' => $searchForm->createView(),
            'activeFilters' => $activeFilters,
        ]);
    }
    
    // ========================= CREATE =========================
    #[Route('BackOffice/admin/resource/new', name: 'admin_resource_new', methods: ['GET','POST'])]
    public function adminNew(Request $request, EntityManagerInterface $em): Response
    {
        $resource = new Resource();
        $form = $this->createForm(ResourceType::class, $resource, [
            'attr' => ['novalidate' => 'novalidate', 'class' => 'space-y-6'],
            'is_edit' => false // Création
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion upload image
            $coverImageFile = $form->get('coverImageFile')->getData();
            
            if ($coverImageFile) {
                $newFilename = uniqid().'.'.$coverImageFile->guessExtension();
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $coverImageFile->move($uploadsDir, $newFilename);
                    
                    $resource->setCoverImage($newFilename);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_resource_new');
                }
            }
            
            // Gestion upload PDF
            $pdfFileFile = $form->get('pdfFileFile')->getData();
            
            if ($pdfFileFile) {
                $newFilename = uniqid().'.'.$pdfFileFile->guessExtension();
                try {
                    $pdfsDir = $this->getParameter('kernel.project_dir') . '/public/pdfs';
                    $pdfFileFile->move($pdfsDir, $newFilename);
                    
                    $resource->setPdfFile($newFilename);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du PDF : ' . $e->getMessage());
                    return $this->redirectToRoute('admin_resource_new');
                }
            }
            
            $em->persist($resource);
            $em->flush();
            
            $this->addFlash('success', 'Ressource ajoutée avec succès');
            return $this->redirectToRoute('admin_resource_index');
        }
        
        return $this->render('BackOffice/admin/resource/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    // ========================= SHOW =========================
    #[Route('BackOffice/admin/resource/{id}', name: 'admin_resource_show', methods: ['GET'])]
    public function adminShow(Resource $resource): Response
    {
        return $this->render('BackOffice/admin/resource/show.html.twig', [
            'resource' => $resource,
        ]);
    }
    
    // ========================= EDIT =========================
    #[Route('BackOffice/admin/resource/{id}/edit', name: 'admin_resource_edit', methods: ['GET','POST'])]
    public function adminEdit(Request $request, Resource $resource, EntityManagerInterface $em): Response
    {
        $oldCoverImage = $resource->getCoverImage();
        $oldPdfFile = $resource->getPdfFile();
        
        // Passer is_edit => true pour le formulaire d'édition
        $form = $this->createForm(ResourceType::class, $resource, [
            'attr' => ['novalidate' => 'novalidate', 'class' => 'space-y-6'],
            'is_edit' => true // Édition
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion upload image
            $coverImageFile = $form->get('coverImageFile')->getData();
            
            if ($coverImageFile) {
                $newFilename = uniqid().'.'.$coverImageFile->guessExtension();
                try {
                    $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
                    $coverImageFile->move($uploadsDir, $newFilename);
                    
                    // Supprimer l'ancienne image
                    if ($oldCoverImage && file_exists($uploadsDir . '/' . $oldCoverImage)) {
                        unlink($uploadsDir . '/' . $oldCoverImage);
                    }
                    
                    $resource->setCoverImage($newFilename);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                    $resource->setCoverImage($oldCoverImage);
                }
            } else {
                // Si pas de nouveau fichier, on garde l'ancien
                $resource->setCoverImage($oldCoverImage);
            }
            
            // Même logique pour le PDF
            $pdfFileFile = $form->get('pdfFileFile')->getData();
            
            if ($pdfFileFile) {
                $newFilename = uniqid().'.'.$pdfFileFile->guessExtension();
                try {
                    $pdfsDir = $this->getParameter('kernel.project_dir') . '/public/pdfs';
                    $pdfFileFile->move($pdfsDir, $newFilename);
                    
                    if ($oldPdfFile && file_exists($pdfsDir . '/' . $oldPdfFile)) {
                        unlink($pdfsDir . '/' . $oldPdfFile);
                    }
                    
                    $resource->setPdfFile($newFilename);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du PDF : ' . $e->getMessage());
                    $resource->setPdfFile($oldPdfFile);
                }
            } else {
                $resource->setPdfFile($oldPdfFile);
            }
            
            $em->flush();
            $this->addFlash('success', 'Ressource modifiée avec succès');
            return $this->redirectToRoute('admin_resource_index');
        }
        
        return $this->render('BackOffice/admin/resource/edit.html.twig', [
            'form' => $form->createView(),
            'resource' => $resource,
        ]);
    }
    
    // ========================= DELETE =========================
    #[Route('BackOffice/admin/resource/{id}/delete', name: 'admin_resource_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Resource $resource, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$resource->getId(), $request->request->get('_token'))) {
            
            // Supprimer l'image associée
            $coverImage = $resource->getCoverImage();
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
            if ($coverImage && file_exists($uploadsDir . '/' . $coverImage)) {
                unlink($uploadsDir . '/' . $coverImage);
            }
            
            // Supprimer le PDF associé
            $pdfFile = $resource->getPdfFile();
            $pdfsDir = $this->getParameter('kernel.project_dir') . '/public/pdfs';
            if ($pdfFile && file_exists($pdfsDir . '/' . $pdfFile)) {
                unlink($pdfsDir . '/' . $pdfFile);
            }
            
            $em->remove($resource);
            $em->flush();
            
            $this->addFlash('success', 'Ressource supprimée avec succès');
        }
        
        return $this->redirectToRoute('admin_resource_index');
    }
}