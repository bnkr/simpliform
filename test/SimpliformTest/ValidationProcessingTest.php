<?php
namespace SimpliformTest;

use PHPUnit_Framework_TestCase as TestCase;
use Simpliform\Form;
use Simpliform\Field;
use SimpliformTest\Fixture;

class ValidationProcessingTest extends TestCase
{
    public function testDelegatedValidationIsCalled()
    {
        $this->fail("ni");
    }
}
