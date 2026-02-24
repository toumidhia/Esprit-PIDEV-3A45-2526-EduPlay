<?php
// src/Service/QrCodeService.php

namespace App\Service;

use App\Entity\EventRegistration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrCodeService
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $projectDir
    ) {}

    public function generateTicketQrCode(EventRegistration $registration): string
    {
        // ✅ Utilisation de l'IP du PC pour le réseau local
        $serverIp = '192.168.100.8'; // IP de ton PC
        
        // Générer une URL accessible depuis le téléphone
        $url = "http://$serverIp:8000/admin/scan/ticket/" . $registration->getTicketQrCode();
        
        // Créer le répertoire s'il n'existe pas
        $qrDir = $this->projectDir . '/public/uploads/qrcodes';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0777, true);
        }
        
        // Nom du fichier
        $filename = 'ticket_' . $registration->getId() . '_' . uniqid() . '.png';
        $filepath = '/uploads/qrcodes/' . $filename;
        
        // Générer le QR code
        $result = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();
        
        // Sauvegarder le fichier
        $result->saveToFile($this->projectDir . '/public' . $filepath);
        
        return $filepath;
    }
    
    public function generateUniqueCode(EventRegistration $registration): string
    {
        return md5($registration->getId() . $registration->getChildFullName() . uniqid());
    }
}