<?php
// src/Controller/TicketController.php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    #[Route('/ticket/{id}', name: 'front_ticket_show', methods: ['GET'])]
    public function showTicket(EventRegistration $registration, QrCodeService $qrCodeService, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        // Vérifier que le parent est bien le propriétaire
        $parentEntity = $this->getUser();
        if ($registration->getParent() !== $parentEntity) {
            throw $this->createAccessDeniedException('Ce ticket ne vous appartient pas.');
        }
        
        // ✅ Si pas de QR code, le générer à la volée
        if (!$registration->getQrCodePath()) {
            // Générer un code unique si nécessaire
            if (!$registration->getTicketQrCode()) {
                $uniqueCode = $qrCodeService->generateUniqueCode($registration);
                $registration->setTicketQrCode($uniqueCode);
            }
            
            $qrCodePath = $qrCodeService->generateTicketQrCode($registration);
            $registration->setQrCodePath($qrCodePath);
            $em->flush();
        }
        
        return $this->render('FrontOffice/Parent/ticket/show.html.twig', [
            'registration' => $registration,
        ]);
    }
}