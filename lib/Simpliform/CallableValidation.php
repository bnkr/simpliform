<?php
namespace Simpliform;

/**
 * Adapts a callable to the validation interface.
 */
class CallableValidation implements ValidationInterface
{
    public function __construct($callable)
    {
       $this->_callable = $callable;
    }

    public function validate($value, $context)
    {
        return (bool) call_user_func($this->_callable, $value, $context);
    }
}
