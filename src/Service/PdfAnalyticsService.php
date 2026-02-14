<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service for extracting analytics from PDF course files
 * This service provides basic PDF analysis capabilities
 */
class PdfAnalyticsService
{
    /**
     * Extract text content from PDF (requires additional library in production)
     * For now, provides basic file information
     */
    public function extractPdfInfo(string $pdfPath): array
    {
        if (!file_exists($pdfPath)) {
            return [
                'success' => false,
                'error' => 'File not found'
            ];
        }

        $fileSize = filesize($pdfPath);
        $fileSizeKB = round($fileSize / 1024, 2);
        
        // Basic file analysis
        $info = [
            'success' => true,
            'fileName' => basename($pdfPath),
            'fileSize' => $fileSizeKB . ' KB',
            'fileSizeBytes' => $fileSize,
            'uploadDate' => date('Y-m-d H:i:s', filemtime($pdfPath)),
            'extension' => pathinfo($pdfPath, PATHINFO_EXTENSION),
        ];

        // Attempt to get PDF page count (basic method)
        try {
            $content = file_get_contents($pdfPath);
            if ($content) {
                // Count  "/Page" occurrences as rough estimate of pages
                preg_match_all("/\/Page\W/", $content, $matches);
                $info['estimatedPages'] = count($matches[0]);
                $info['fileHash'] = md5_file($pdfPath);
            }
        } catch (\Exception $e) {
            $info['analysisNote'] = 'Advanced PDF analysis requires installation of PDF processing library';
        }

        return $info;
    }

    /**
     * Generate analytics report for a course PDF
     */
    public function generatePdfStats(string $pdfPath): array
    {
        $basicInfo = $this->extractPdfInfo($pdfPath);
        
        if (!$basicInfo['success']) {
            return $basicInfo;
        }

        // Additional analytics
        $stats = array_merge($basicInfo, [
            'isAccessible' => is_readable($pdfPath),
            'lastModified' => date('Y-m-d H:i:s', filemtime($pdfPath)),
            'lastAccessed' => date('Y-m-d H:i:s', fileatime($pdfPath)),
        ]);

        return $stats;
    }

    /**
     * Validate PDF quality and requirements
     */
    public function validatePdf(UploadedFile $file): array
    {
        $errors = [];
        $warnings = [];

        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            $errors[] = 'Le fichier PDF dépasse la taille maximale de 10 MB';
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, ['application/pdf', 'application/x-pdf'])) {
            $errors[] = 'Le fichier doit être au format PDF';
        }

        // Warnings for very small files
        if ($file->getSize() < 50 * 1024) {
            $warnings[] = 'Le fichier PDF semble très petit (moins de 50 KB)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'fileInfo' => [
                'originalName' => $file->getClientOriginalName(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'mimeType' => $mimeType,
            ]
        ];
    }

    /**
     * Get PDF statistics for analytics dashboard
     */
    public function getPdfStatistics(array $courses): array
    {
        $stats = [
            'totalPdfs' => 0,
            'totalSize' => 0,
            'coursesWithPdf' => 0,
            'coursesWithoutPdf' => 0,
            'avgFileSize' => 0,
        ];

        foreach ($courses as $course) {
            if ($course->getPdfFile()) {
                $stats['totalPdfs']++;
                $stats['coursesWithPdf']++;
                
                $pdfPath = __DIR__ . '/../../public/uploads/courses/' . $course->getPdfFile();
                if (file_exists($pdfPath)) {
                    $stats['totalSize'] += filesize($pdfPath);
                }
            } else {
                $stats['coursesWithoutPdf']++;
            }
        }

        if ($stats['totalPdfs'] > 0) {
            $stats['avgFileSize'] = round(($stats['totalSize'] / $stats['totalPdfs']) / 1024, 2);
        }

        $stats['totalSizeMB'] = round($stats['totalSize'] / (1024 * 1024), 2);

        return $stats;
    }

    /**
     * Note: For production environment, consider installing:
     * - smalot/pdfparser: composer require smalot/pdfparser
     * - tecnickcom/tcpdf: composer require tecnickcom/tcpdf
     * 
     * These libraries provide advanced PDF processing:
     * - Full text extraction
     * - Metadata reading
     * - Page count accuracy
     * - Content analysis
     */
}
