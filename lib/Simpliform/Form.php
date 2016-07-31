<?php
namespace Simpliform;

/**
 * Processing events happen when matched to this trigger.
 */
interface TriggerInterface
{
    /**
     * Return true if an associated processing object should execute.
     */
    public function matches($event);
}

/**
 * Validation and processing logic.
 */
interface ProcessingInterface
{
    /**
     * Run changes and return the value or raise an error to record a validation
     * problem.
     *
     * TODO
     *   If we have processing that changes multiple fields this can be a
     *   problem.  Such a processor would be useful in, for example, a file
     *   upload field which memorizes the upload from an invalid submission.
     */
    public function execute($context);
}

/**
 * A validator is a processor which makes no changes to the data, instead it
 * checks the validitiy.
 */
interface ValidationInterface
{
    /**
     * Checks the value in the given context and raises an error if there's a
     * problem.
     *
     * TODO:
     *   Probably wrong.  We should return a list so that there can be multiple
     *   validation errors returned.
     */
    public function validate($value, $context);
}

/**
 * Adapts a callable to the trigger interface.
 */
class CallableTrigger
{
    public function __construct($callable)
    {
       $this->_callable = $callable;
    }

    public function matches($event)
    {
        return (bool) call_user_func($this->_callable, $event);
    }
}

/**
 * Adapts a callable to the validation interface.
 */
class CallableValidation implements ValidationInterface
{
    public function __construct($callable)
    {
       $this->_callable = $callable;
    }

    public function validate($value, $context)
    {
        return (bool) call_user_func($this->_callable, $value, $context);
    }
}

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

    public function getForm()
    {
        return $this->_form;
    }
}


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

/**
 * Takes an object which validates the field in context but applies no change to
 * the value given.  This is run as a processor.
 */
class ValidationProcessing implements ProcessingInterface
{
    public function __construct($validation)
    {
        $this->validation = $validation;
    }

    public function execute($context)
    {
        $value = $context->getValue();
        $this->validation->validate($value, $context);
        return $value;;
    }
}

/**
 * Wraps a callable for a post-data configuration process.
 */
class CallablePreparation
{
    public function __construct($preparation)
    {
        $this->_preparation = $preparation;
    }

    public function execute($form)
    {
        call_user_func($this->_preparation, $form);
    }
}

/**
 * Main class for defining forms.
 */
class Form
{
    private $_hasProcessed = false;
    private $_data = null;
    private $_processed = null;
    private $_isValid = false;
    private $_preparation = array();
    private $_processing = array();

    public function __construct()
    {
    }

    /**
     * Assign the data which will be processed bu this form.
     */
    public function setData($data)
    {
        $this->_data = $data;
    }

    /**
     * Return the current data.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Preparation changes the form after input data has been set.
     */
    public function addPreparation($preparation)
    {
        $this->_preparation[] = $this->toPreparation($preparation);
    }

    static private function toPreparation($param)
    {
        if (is_callable($param)) {
            return new CallablePreparation($param);
        } else {
            throw new \Exception("can't");
        }
    }

    /**
     * Processing changes the data on a given trigger.
     */
    public function addProcessing($triggerOrProcessing, $processing = null)
    {
        if ($processing) {
            $trigger = $triggerOrProcessing;
        } else {
            $processing = $triggerOrProcessing;
            $trigger = $triggerOrProcessing;
        }
        $trigger = $this->toTrigger($trigger);
        $processing = $this->toProcessing($processing);
        $this->_processing[] = array($trigger, $processing);
    }

    // TODO:
    //   this needs testing, should probably be a factory somewhere so we can
    //   unit it (though w do need to care about the behaviour of optional
    //   parameters).
    static private function toTrigger($trigger)
    {
        if (is_string($trigger)) {
            return new CallableTrigger(function($event) use($trigger) {
                return $event->getFieldName() == $trigger;
            });
        } else if (is_callable($trigger)) {
            return new CallableTrigger($trigger);
        } else {
            self::throwTypeError($trigger);
        }
    }

    static private function throwTypeError($value)
    {
        if (gettype($value) == 'object') {
            throw new \Exception("bad type: " . gettype($value) . " (" . get_class($value) . ")");
        } else {
            throw new \Exception("bad type: " . gettype($value));
        }
    }

    static private function toProcessing($param)
    {
        if (is_callable($param)) {
            return new CallableProcessing($param);
        } else if ($param instanceof ProcessingInterface) {
            return $param;
        } else {
            self::throwTypeError($trigger);
        }
    }

    static private function toValidation($param)
    {
        if (is_callable($param)) {
            return new CallableValidation($param);
        } else if ($param instanceof ValidationInterface) {
            return $param;
        } else {
            self::throwTypeError($trigger);
        }
    }

    /**
     * Validation is a wrapper around processing which expects no changes to be
     * made but possible exceptions to be raised.
     */
    public function addValidation($trigger, $validation)
    {
        $this->addProcessing($trigger, new ValidationProcessing($this->toValidation($validation)));
    }


    /**
     * True if the form has data an the data was pprocessed successfully and the
     * form was not otherwise set invalid.  Otherwise false.
     */
    public function isValid()
    {
        if ($this->_hasProcessed) {
            return $this->_isValid;
        }

        $this->process();
        $this->_hasProcessed = true;
        return $this->_isValid;
    }

    /**
     * Turn the raw data into PHP data, recording validation errors along the
     * way.
     */
    private function process()
    {
        foreach ($this->_preparation as $preparation) {
            $preparation->execute($this);
        }

        $this->_processed = array();
        // TODO:
        //   a sequence like this won't work for subgroups or lists (obviously)
        //   but we can be recursive easily enough.
        foreach ($this->_data as $key => $value) {
            $this->_loadStack = array();
            $this->load($key);
        }

    }

    private function load($field)
    {
        if (array_key_exists($field, $this->_processed)) {
            return $this->_processed[$field];
        }

        if (in_array($field, $this->_loadStack)) {
            throw new \Exception("circular dependency: $field");
        }

        $this->_loadStack[] = $field;

        $event = new ProcessingEvent();
        $event->_name = $field;

        $value = $this->_data[$field];
        foreach ($this->_processing as $processing) {
            list($trigger, $process) = $processing;


            if ($trigger->matches($event)) {
                $context = new ProcessingContext();
                $context->_value = $value;
                $context->_form = $this;
                $value = $process->execute($context);
            }
        }

        $this->_processed[$field] = $value;
        return $value;
    }

    public function __call($method, $args)
    {
        throw new \Exception("no such method: $method");
    }
}
