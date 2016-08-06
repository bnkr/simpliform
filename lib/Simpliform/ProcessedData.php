<?php
namespace Simpliform;

/**
 * Simple DTO returned by processors.
 */
class ProcessedData
{
    public $value = null;
    public $errors = array();

    public function __construct($value = null)
    {
        $this->value = $value;
    }
}
