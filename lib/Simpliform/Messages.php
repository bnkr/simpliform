<?php
namespace Simpliform;

/**
 * Information reported on each field.  Doesn't necessarily have to be errors.
 */
class Messages
{
    private $_messages = array();

    public function add($field, $error)
    {
        $this->_messages[$field][] = $error;
    }

    public function get($field)
    {
        if (isset($this->_messages[$field])) {
            return new FieldMessages($field, $this->_messages[$field]);
        } else {
            return new FieldMessages($field, array());
        }
    }

    // TODO: isValid could be a state, so we can have ERROR vs WARNING state for
    // eample.
    public function isValid()
    {
        return (bool) $this->_messages;
    }
}
