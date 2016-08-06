<?php
namespace Simpliform;

/**
 * Information reported on each field.  Doesn't necessarily have to be errors.
 */
class Messages
{
    private $_messages = array();

    /**
     * Return a list of field => [message, ...].  Useful developer readable
     * purposes.  If there is a tree of fields then it will be
     * fieldgroup.0.field => [message].
     */
    public function toFlatList()
    {
        $list = array();
        foreach ($this->_messages as $field => $messages) {
            if (isset($list[$field])) {
                $list[$field] = array();
            }

            // TODO: needs test if we do this kind of thing
            foreach ($messages as $message) {
                if ($message instanceof ValidationException) {
                    $message = $message->getMessage();
                }
                $list[$field][] = $message;
            }
        }
        return $list;
    }

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
