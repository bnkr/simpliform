<?php
namespace Simpliform;

/**
 * Identifies where we are in the processing process.  Processing objects will
 * match this event to decide whether they need to run or not.
 */
class ProcessingEvent
{
    public function getFieldName()
    {
        return $this->_name;
    }
}
