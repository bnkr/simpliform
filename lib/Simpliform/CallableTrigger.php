<?php
namespace Simpliform;

/**
 * Adapts a callable to the trigger interface.
 */
class CallableTrigger implements TriggerInterface
{
    public function __construct($callable)
    {
       $this->_callable = $callable;
    }

    public function matches($event)
    {
        return (bool) call_user_func($this->_callable, $event);
    }
}
