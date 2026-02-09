<?php

namespace App\Controller;

use App\Entity\Library;
use App\Form\LibraryType;
use App\Repository\LibraryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Form\LibrarySearchType;
final class LibraryController extends AbstractController
{
    // ===============================
    // FRONT OFFICE - Afficher toutes les libraries
    // ===============================
    
    #[Route('FrontOffice/library', name: 'app_library')]
    public function index(LibraryRepository $libraryRepository): Response
    {
        return $this->render('FrontOffice/library/index.html.twig', [
            'libraries' => $libraryRepository->findAll(),
        ]);
    }
    
    // ===============================
    // BACK OFFICE (Admin) - Dashboard
    // ===============================
    
    #[Route('BackOffice/admin', name: 'app_admin')]
    public function adminDashboard(): Response
    {
        return $this->render('BackOffice/admin/base_admin.html.twig', [
            'controller_name' => 'LibraryController',
        ]);
    }
    
    // ===============================
    // BACK OFFICE - CRUD Operations
    // ===============================
    
   // ========================= LIST =========================
#[Route('BackOffice/admin/library/', name: 'admin_library_index', methods: ['GET'])]
public function adminIndex(Request $request, LibraryRepository $libraryRepository): Response
{
    $form = $this->createForm(LibrarySearchType::class);
    $form->handleRequest($request);
    
    $libraries = [];
    $filters = [];
    
    if ($form->isSubmitted()) {
        $data = $form->getData();
        
        // Si c'est le bouton "Réinitialiser"
        if ($form->get('reset')->isClicked()) {
            return $this->redirectToRoute('admin_library_index');
        }
        
        // Appliquer les filtres
        $filters = array_filter($data, function($value) {
            return $value !== null && $value !== '';
        });
        
        $libraries = $libraryRepository->search($filters);
    } else {
        // Par défaut, toutes les bibliothèques triées par nom
        $libraries = $libraryRepository->findBy([], ['name' => 'ASC']);
    }
    
    return $this->render('BackOffice/admin/library/index.html.twig', [
        'libraries' => $libraries,
        'searchForm' => $form->createView(),
        'activeFilters' => $filters
    ]);
}
    
    // ========================= CREATE =========================
    #[Route('BackOffice/admin/library/new', name: 'admin_library_new', methods: ['GET','POST'])]
    public function adminNew(Request $request, EntityManagerInterface $em): Response
    {
        $library = new Library();
        $form = $this->createForm(LibraryType::class, $library, [
            'attr' => ['novalidate' => 'novalidate', 'class' => 'space-y-6'] // Désactive HTML5
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Gestion upload image
                $imageFile = $form->get('coverImageFile')->getData();
                
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                        
                        // Sauvegarder le nom du fichier
                        $library->setCoverImage($newFilename);
                        
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                        return $this->redirectToRoute('admin_library_new');
                    }
                }
                
                $em->persist($library);
                $em->flush();
                
                $this->addFlash('success', 'Library ajoutée avec succès');
                
                return $this->redirectToRoute('admin_library_index');
            } else {
                // Ajouter un message d'erreur général
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }
        
        return $this->render('BackOffice/admin/library/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    // ========================= SHOW =========================
    #[Route('BackOffice/admin/library/{id}', name: 'admin_library_show', methods: ['GET'])]
    public function adminShow(Library $library): Response
    {
        return $this->render('BackOffice/admin/library/show.html.twig', [
            'library' => $library,
        ]);
    }
    
    // ========================= EDIT =========================
    #[Route('BackOffice/admin/library/{id}/edit', name: 'admin_library_edit', methods: ['GET','POST'])]
    public function adminEdit(Request $request, Library $library, EntityManagerInterface $em): Response
    {
        $oldImage = $library->getCoverImage();
        
        $form = $this->createForm(LibraryType::class, $library, [
            'attr' => ['novalidate' => 'novalidate', 'class' => 'space-y-6'] // Désactive HTML5
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Gestion upload image
                $imageFile = $form->get('coverImageFile')->getData();
                
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                        
                        // Supprimer l'ancienne image si elle existe
                        if ($oldImage && file_exists($this->getParameter('uploads_directory') . '/' . $oldImage)) {
                            unlink($this->getParameter('uploads_directory') . '/' . $oldImage);
                        }
                        
                        $library->setCoverImage($newFilename);
                        
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                        // En cas d'erreur, garder l'ancienne image
                        $library->setCoverImage($oldImage);
                    }
                } else {
                    // IMPORTANT : Si aucune nouvelle image, garder l'ancienne
                    $library->setCoverImage($oldImage);
                }
                
                $em->flush();
                
                $this->addFlash('success', 'Library modifiée avec succès');
                
                return $this->redirectToRoute('admin_library_index');
            } else {
                // Ajouter un message d'erreur général
                $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
            }
        }
        
        return $this->render('BackOffice/admin/library/edit.html.twig', [
            'form' => $form->createView(),
            'library' => $library,
        ]);
    }
    
    // ========================= DELETE =========================
    #[Route('BackOffice/admin/library/{id}/delete', name: 'admin_library_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Library $library, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$library->getId(), $request->request->get('_token'))) {
            
            // Supprimer l'image associée
            $imageName = $library->getCoverImage();
            if ($imageName && file_exists($this->getParameter('uploads_directory') . '/' . $imageName)) {
                unlink($this->getParameter('uploads_directory') . '/' . $imageName);
            }
            
            $em->remove($library);
            $em->flush();
            
            $this->addFlash('success', 'Library supprimée avec succès');
        }
        
        return $this->redirectToRoute('admin_library_index');
    }
}