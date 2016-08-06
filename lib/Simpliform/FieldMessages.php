<?php
namespace Simpliform;

/**
 * Messages for just one field.  This would be used in the form's messages
 * structure.
 */
class FieldMessages
{
    public function __construct($field, $messages)
    {
        $this->_field = $field;
        $this->_messages = $messages;
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    public function add($message)
    {
        $this->_messages[] = $message;
    }

    public function isValid()
    {
        return ! $this->_messages;
    }
}
