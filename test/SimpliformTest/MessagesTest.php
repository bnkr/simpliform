<?php
namespace SimpliformTest;

use PHPUnit_Framework_TestCase as TestCase;
use Simpliform\Form;
use Simpliform\Messages;
use SimpliformTest\Fixture;

class MessagesTest extends TestCase
{
    public function testEmptyMessagesIsEmpty()
    {
        $messages = new Messages();
        $this->assertEquals(true, $messages->isValid());
        $this->assertEquals(true, $messages->isEmpty());
        $this->assertEquals(array(), $messages->toFlatList());
    }

    public function testMessagesFlattenedAndPretified()
    {
        // check validationException is convered properly
        // and any other magical values of an erro (e.g. warning, failure type)
        // should be visible in the output
        $this->fail("ni");
    }
}
