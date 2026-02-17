<?php
// src/Service/PdfExtractorService.php

namespace App\Service;

use Smalot\PdfParser\Parser;
use Psr\Log\LoggerInterface;

class PdfExtractorService
{
    private $logger;
    private $projectDir;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    /**
     * Extrait le texte d'un fichier PDF
     */
    public function extractTextFromPdf(string $pdfFilename): ?string
    {
        try {
            $pdfPath = $this->projectDir . '/public/pdfs/' . $pdfFilename;
            
            if (!file_exists($pdfPath)) {
                $this->logger->error('Fichier PDF non trouvé: ' . $pdfPath);
                return null;
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $text = $pdf->getText();

            // Nettoyer le texte
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            return $text;

        } catch (\Exception $e) {
            $this->logger->error('Erreur extraction PDF: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extrait le texte du PDF page par page
     */
    public function extractTextByPages(string $pdfFilename): array
    {
        try {
            $pdfPath = $this->projectDir . '/public/pdfs/' . $pdfFilename;
            
            if (!file_exists($pdfPath)) {
                return [];
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            
            $texts = [];
            foreach ($pages as $index => $page) {
                $text = $page->getText();
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                
                if (!empty($text)) {
                    $texts[$index + 1] = $text;
                }
            }

            return $texts;

        } catch (\Exception $e) {
            $this->logger->error('Erreur extraction pages PDF: ' . $e->getMessage());
            return [];
        }
    }
}