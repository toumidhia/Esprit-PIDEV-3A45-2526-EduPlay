<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SimpleFile extends Constraint
{
    public $message = 'The file "{{ file }}" is not valid.';
    public $maxSizeMessage = 'The file is too large ({{ size }} {{ suffix }}). Maximum allowed size is {{ limit }} {{ suffix }}.';
    public $maxSize = '10M';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}