<?php
namespace Simpliform\Field;

/**
 * Field built up from closures for various purposes.
 */
class GenericField
{
    public function addProcessing($processing)
    {
        $this->_processing[] = $processing;
    }
}
