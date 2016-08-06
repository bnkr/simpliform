<?php
namespace Simpliform;

/**
 * Performs processing causde by a field.
 */
class FieldProcessing implements ProcessingInterface
{
    public function __construct($field)
    {
        $this->_field = $field;
    }

    public function execute($context)
    {
        // TODO:
        //   Poor prototype.  It would be better that this got loaded into the
        //   Form,m otherise we have to have two copies of the algorithm.
        return call_user_func($this->_field->_processing[0], $context);
    }
}
