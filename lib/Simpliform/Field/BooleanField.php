<?php
namespace Simpliform\Field;

/**
 * Reads a zero or a one and converts it into true or false.
 */
class BooleanField
{
    public function __construct()
    {
        // it's nice to be general but it's a bit contrived to store a closure
        // calling this object if we're always going to work with an object with
        // a toPhp method...
        $this->_processing = array(array($this, 'toPhp'));
    }

    public function toPhp($context)
    {
        return $context->getValue() ? true : false;
    }

    public function __call($method, $args)
    {
        throw new \Exception("no such method: $method");
    }
}
