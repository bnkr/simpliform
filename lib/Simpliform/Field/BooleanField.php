<?php
namespace Simpliform\Field;

/**
 * Reads a zero or a one and converts it into true or false.
 */
class BooleanField
{
    public function __call($method, $args)
    {
        throw new \Exception("no such method: $method");
    }
}
