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

    public function testErrorTextStored()
    {
        $messages = new Messages();
        $messages->add('field', "plain ole text");
        $this->assertEquals(false, $messages->isValid());
        $this->assertEquals(false, $messages->isEmpty());
        $this->assertEquals(array('field' => array('plain ole text')), $messages->toFlatList());
    }

    public function testMultipleErrorsStored()
    {
        $messages = new Messages();
        $messages->add('field', "plain ole text");
        $messages->add('field', "more ole text");
        $this->assertEquals(false, $messages->isValid());
        $this->assertEquals(false, $messages->isEmpty());
        $this->assertEquals(array('field' => array('plain ole text', 'more ole text')), $messages->toFlatList());
    }

    public function testValidaationErrorStored()
    {
        $messages = new Messages();
        $messages->add('field', new \Simpliform\ValidationException("plain ole text"));
        $this->assertEquals(false, $messages->isValid());
        $this->assertEquals(false, $messages->isEmpty());
        $this->assertEquals(array('field' => array('plain ole text')), $messages->toFlatList());
    }
}
