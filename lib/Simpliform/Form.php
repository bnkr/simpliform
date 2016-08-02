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
     * Run changes and return a ProcessedData object which gives the value(s)
     * and a list of errors.
     *
     * TODO
     *   How do we implement multiple values being returned?
     *
     *   How do we implement warnings?
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
     * Checks the value in the given context.  Returns a list of errors or a
     * true value when it's valid or raises a validation error.
     */
    public function validate($value, $context);
}

class ProcessedData
{
    public $value = null;
    public $errors = array();
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
 * Adapts a callable to the processing interface.
 */
class CallableProcessing
{
    public function __construct($callable)
    {
       $this->_callable = $callable;
    }

    public function execute($context)
    {
        return (bool) call_user_func($this->_callable, $context);
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
 * Raised by processors to indicate invalid data.
 */
class ValidationException extends \Exception
{
}

class ErrorMessages
{
    public function __construct($field, $messages)
    {
        $this->field = $field;
        $this->messages = $messages;
    }

    public function isValid()
    {
        return (bool) $this->messages;
    }
}

/**
 * Information about messages on each field.
 *
 * TODO:
 *   "error" is a wrong name if we allow warning states
 */
class Errors
{
    private $_errors = array();

    public function add($field, $error)
    {
        $this->_errors[$field][] = $error;
    }

    public function get($field)
    {
        if (isset($this->_errors[$field])) {
            return new ErrorMessages($field, $this->_errors[$field]);
        } else {
            return new ErrorMessages($field, array());
        }
    }

    public function isValid()
    {
        return (bool) $this->_errors;
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
        $data = new ProcessedData();
        $value = $context->getValue();
        $data->value = $value;

        // TODO:
        //   This is messy.  If we're using the "fail" method then a null return
        //   value just means no exception was raised.  We shouldn't really have
        //   to do both ...
        $errors = $this->validation->validate($value, $context);

        if (! is_array($errors) && ! $errors) {
            $errors = array("Validation returned false.");
        } else if (! is_array($errors) && $errors) {
            $errors = array();
        }

        foreach (($errors ?: array()) as $error) {
            $data->errors[] = $error;
        }

        return $data;
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
    private $_disables = array();

    public function __construct()
    {
    }

    /**
     * Assign the data which will be processed bu this form.
     */
    public function setData($data)
    {
        $this->_processed = null;
        $this->_isValid = false;
        $this->_hasProcessed = false;
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

    /**
     * Validation is a wrapper around processing which expects no changes to be
     * made but possible exceptions to be raised.
     */
    public function addValidation($triggerOrProcessing, $validation = null)
    {
        if ($validation) {
            $trigger = $triggerOrProcessing;
        } else {
            $validation = $triggerOrProcessing;
            $trigger = $triggerOrProcessing;
        }
        $this->addProcessing($trigger, new ValidationProcessing($this->toValidation($validation)));
    }

    /**
     * Perform no processing for the given trigger.  This is useful to
     * completely ignore a field if the user has checked a delete button.
     */
    public function disable($trigger)
    {
        $this->_disables[] = $this->toTrigger($trigger);
    }

    public function setDetails($data)
    {
        throw new \Exception();
    }

    public function getState()
    {
        // 'valid', 'innvalid', 'not-processed',
        // can we allow an arbitrary state?
    }

    /**
     * True if the form has data an the data was pprocessed successfully and the
     * form was not otherwise set invalid.  Otherwise false.
     */
    public function isValid()
    {
        $this->process();
        return $this->_isValid;
    }

    /**
     * Return errors mapped from fields.
     */
    public function getErrors()
    {
        $this->process();
        return $this->_errors;
    }

    /**
     * Turn the raw data into PHP data, recording validation errors along the
     * way.
     */
    private function process()
    {
        if ($this->_hasProcessed) {
            return;
        }

        foreach ($this->_preparation as $preparation) {
            $preparation->execute($this);
        }

        $this->_processed = array();
        $this->_isValid = true;

        // TODO:
        //   a sequence like this won't work for subgroups or lists (obviously)
        //   but we can be recursive easily enough.
        //
        //   additionally, if we could make groups include their own processing
        //   that would make it easier to re-use things.
        foreach ($this->_data as $key => $value) {
            $this->_loadStack = array();
            $this->load($key);
        }
    }

    /**
     * Cause a field to be evaluated.
     */
    public function load($field)
    {
        if (array_key_exists($field, $this->_processed)) {
            return $this->_processed[$field];
        }

        // TODO: needs unit testing (or did I already cover this?)
        if (in_array($field, $this->_loadStack)) {
            $this->_loadStack[] = $field;
            throw new \Exception("circular dependency: " . implode(" -> ", $this->_loadStack));
        }

        $this->_loadStack[] = $field;

        $event = new ProcessingEvent();
        $event->_name = $field;

        // TODO:
        //   If we do this then we can repeatedly try to load this field.
        //   Additionally if a processor is loading this field manially then it
        //   might not know that a null means the field was disabled.  It might
        //   be better to raise an error and then mark the field fisabled in a
        //   separate map.
        foreach ($this->_disables as $trigger) {
            if ($trigger->matches($event)) {
                return null;
            }
        }

        $this->_errors = new Errors();
        $value = $this->_data[$field];
        foreach ($this->_processing as $processing) {
            list($trigger, $process) = $processing;

            if ($trigger->matches($event)) {
                $context = new ProcessingContext();
                $context->_value = $value;
                $context->_form = $this;

                try {
                    $processed = $process->execute($context);
                } catch (ValidationException $ex) {
                    $this->_errors->add($event->getFieldName(), $ex);
                    $value = null;
                    break;
                }

                foreach ($processed->errors as $error) {
                    $this->_isValid = false;
                    $this->_errors->add($event->getFieldName(), $error);
                }

                $value = $processed->value;
            }
        }

        $this->_processed[$field] = $value;
        return $value;
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

    static private function toPreparation($param)
    {
        if (is_callable($param)) {
            return new CallablePreparation($param);
        } else {
            throw new \Exception("can't");
        }
    }


    public function __call($method, $args)
    {
        throw new \Exception("no such method: $method");
    }
}
