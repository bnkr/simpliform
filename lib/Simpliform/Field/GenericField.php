<?php
namespace Simpliform\Field;

/**
 * Field built up from closures for various purposes.
 */
class GenericField
{
    public function __construct()
    {
        $this->_processing = array();
        $this->_required = false;

        $that = $this;
        $this->addProcessing(function($context) use($that) {
            if (! $that->_required) {
                return $context->getValue();
            }

            if (empty($context->getValue())) {
                throw new \Simpliform\ValidationError("data missing or empty");
            }

            // TODO:
            //   Why not call setValue instead of using return?
            return $context->getValue();
        });
    }

    public function setRequired($bool)
    {
        $this->_required = (bool) $bool;
    }

    public function addProcessing($processing)
    {
        $this->_processing[] = $processing;
    }
}
