<?php
namespace Simpliform;

/**
 * Main class for defining forms.
 */
class Form
{
    public function __call($method, $args)
    {
        throw new \Exception("no such method: $method");
    }
}
