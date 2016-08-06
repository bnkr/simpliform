<?php
namespace Simpliform;

/**
 * Data at the time processing is being done.  This is cwjust a convenient way
 * to send lots of data to the processor without having billions of parameters.
 */
class ProcessingContext
{
    public function getValue()
    {
        return $this->_value;
    }

    public function getRawData()
    {
        return $this->getForm()->getData();
    }

    public function getForm()
    {
        return $this->_form;
    }

    public function get($field)
    {
        return $this->getForm()->load($field);
    }

    public function fail($error)
    {
        throw new ValidationException($error);
    }
}
