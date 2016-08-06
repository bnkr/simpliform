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

    public function isValid()
    {
        return (bool) $this->_messages;
    }
}
