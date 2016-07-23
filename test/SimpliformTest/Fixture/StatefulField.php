<?php
namespace SimpliformTest\Fixture;

class StatefulField
{
    public function __call($method, $args)
    {
        throw new \Exception("no such method: $method");
    }
}
