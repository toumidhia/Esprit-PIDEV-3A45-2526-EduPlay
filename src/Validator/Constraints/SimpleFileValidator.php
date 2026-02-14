<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SimpleFileValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof SimpleFile) {
            throw new UnexpectedTypeException($constraint, SimpleFile::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        // Check file size
        $maxSize = $this->parseSize($constraint->maxSize);
        if ($value->getSize() > $maxSize) {
            $this->context->buildViolation($constraint->maxSizeMessage)
                ->setParameter('{{ file }}', $value->getClientOriginalName())
                ->setParameter('{{ size }}', $this->formatSize($value->getSize()))
                ->setParameter('{{ limit }}', $this->formatSize($maxSize))
                ->setParameter('{{ suffix }}', 'MB')
                ->addViolation();
        }

        // Check extension (not MIME type)
        $extension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ file }}', $value->getClientOriginalName())
                ->addViolation();
        }
    }

    private function parseSize($size)
    {
        if (preg_match('/^(\d+)([KMG]?)$/i', $size, $matches)) {
            $value = (int)$matches[1];
            $unit = strtoupper($matches[2]);

            switch ($unit) {
                case 'G': return $value * 1024 * 1024 * 1024;
                case 'M': return $value * 1024 * 1024;
                case 'K': return $value * 1024;
                default: return $value;
            }
        }

        return 10485760; // Default 10MB
    }

    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2);
    }
}