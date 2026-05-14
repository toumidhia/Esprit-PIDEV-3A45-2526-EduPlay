<?php
// src/Controller/Admin/ScanController.php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/scan')]
class ScanController extends AbstractController
{
    #[Route('', name: 'admin_scan_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('BackOffice/admin/scan/index.html.twig');
    }
    
    #[Route('/verify', name: 'admin_scan_verify', methods: ['GET', 'POST'])]
    public function verify(Request $request, EventRegistrationRepository $repo, EntityManagerInterface $em): Response
    {
        $code = $request->get('code') ?? $request->request->get('code');
        
        if (!$code) {
            $this->addFlash('error', 'Veuillez saisir un code');
            return $this->redirectToRoute('admin_scan_index');
        }
        
        // Nettoyer le code (si c'est une URL complète)
        if (strpos($code, 'http') === 0) {
            $parts = explode('/', $code);
            $code = end($parts);
        }
        
        $registration = $repo->findOneBy(['ticketQrCode' => $code]);
        
        if (!$registration) {
            $this->addFlash('error', 'Ticket invalide !');
            return $this->redirectToRoute('admin_scan_index');
        }
        
        if ($registration->isScanned()) {
            $scannedAt = $registration->getScannedAt();
            $dateMessage = $scannedAt ? $scannedAt->format('d/m/Y H:i') : 'date inconnue';
            
            $this->addFlash('warning', 
                'Ce ticket a déjà été scanné le ' . $dateMessage
            );
            return $this->redirectToRoute('admin_scan_index');
        }
        
        $registration->setScannedAt(new \DateTime());
        $em->flush();
        
        $this->addFlash('success', '✅ Ticket valide ! Entrée autorisée pour ' . $registration->getChildFullName());
        
        return $this->redirectToRoute('admin_scan_index');
    }

    #[Route('/ticket/{code}', name: 'admin_scan_ticket', methods: ['GET'])]
    public function scanTicket(string $code, EventRegistrationRepository $repo, EntityManagerInterface $em): Response
    {
        $registration = $repo->findOneBy(['ticketQrCode' => $code]);
        
        if (!$registration) {
            $this->addFlash('error', 'Ticket invalide !');
            return $this->redirectToRoute('admin_scan_index');
        }
        
        if ($registration->isScanned()) {
            $scannedAt = $registration->getScannedAt();
            $dateMessage = $scannedAt ? $scannedAt->format('d/m/Y H:i') : 'date inconnue';
            
            $this->addFlash('warning', 
                'Ce ticket a déjà été scanné le ' . $dateMessage
            );
            return $this->redirectToRoute('admin_scan_index');
        }
        
        $registration->setScannedAt(new \DateTime());
        $em->flush();
        
        $this->addFlash('success', '✅ Ticket valide ! Entrée autorisée pour ' . $registration->getChildFullName());
        
        return $this->redirectToRoute('admin_scan_index');
    }
}