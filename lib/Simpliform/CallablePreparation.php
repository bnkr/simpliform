<?php
namespace Simpliform;

/**
 * Wraps a callable for a post-data configuration process.
 */
class CallablePreparation
{
    public function __construct($preparation)
    {
        $this->_preparation = $preparation;
    }

    public function execute($form)
    {
        call_user_func($this->_preparation, $form);
    }
}
