<?php
namespace Simpliform;

/**
 * Adapts a callable to the processing interface.
 */
class CallableProcessing implements ProcessingInterface
{
    public function __construct($callable)
    {
       $this->_callable = $callable;
    }

    public function execute($context)
    {
        return (bool) call_user_func($this->_callable, $context);
    }
}
