<?php
namespace Simpliform;

/**
 * Information reported on each field.  Doesn't necessarily have to be errors.
 */
class Messages
{
    // private $_messages = array();
    public function __construct()
    {
        $this->_messages = array();
    }

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
            foreach ($messages->getMessages() as $message) {
                if ($message instanceof ValidationException) {
                    $message = substr($message->getMessage(), 0, 256);
                }
                $list[$field][] = $message;
            }
        }
        return $list;
    }

    /**
     * Mark the field as having a given error.
     *
     * TODO:
     *   We will need to allow more data about why an error happened, e.g. which
     *   processing caused the error would be nice.  In this case we could
     *   receive the processing context instead of the field name?
     */
    public function add($field, $error)
    {
        if (! isset($this->_messages[$field])) {
            $this->_messages[$field] = new FieldMessages($field, array());
        }

        $this->_messages[$field]->add($error);
    }

    public function get($field)
    {
        if (isset($this->_messages[$field])) {
            return $this->_messages[$field];
        } else {
            return new FieldMessages($field, array());
        }
    }

    /**
     * True if any messages were reported.
     */
    public function isEmpty()
    {
        return ! $this->_messages;
    }

    /**
     * True if any validation failure messages were reported.
     */
    public function isValid()
    {
        return $this->isEmpty();
    }
}
