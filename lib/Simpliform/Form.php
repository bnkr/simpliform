<?php
namespace Simpliform;

/**
 * Main class for defining forms.
 */
class Form
{
    private $_data = null;
    private $_fields = array();
    private $_messages = null;
    private $_processed = null;
    private $_state = null;
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
        $this->reset();
        $this->_data = $data;
    }

    /**
     * Sometimes it can be convenient to re-use a form, or at leat wipe it so
     * you can show it as new.
     */
    public function reset()
    {
        $this->_data = null;
        $this->_processed = null;
    }

    /**
     * Return the input data.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Return the processed data.
     */
    public function getOutput()
    {
        $this->process();
        return $this->_processed;
    }

    /**
     * Specify that a field exists.  By default the keys of the data array will
     * be used.  The field object is more to do with creating a re-usable set of
     * processing objects than doing anything itself..
     */
    public function addField($name, $object = null, $options = null) {
        $params = array();
        if (is_string($name)) {
            $params['name'] = $name;
        } else if (is_array($name)) {
            $params = $name;
        }
        $name = $params['name'];

        if (is_object($object)) {
            $params['object'] = $object;
        }

        if ($options) {
            $params['options'] = $options;
        } else if (! isset($params['options'])) {
            $params['options'] = array();
        }

        $object = $this->toField($params['object']);

        foreach ($params['options'] as $name => $value) {
            $method = 'set' . $name;
            if (! method_exists($object, $method)) {
                throw new \Exception("no such option: $method");
            }

            $object->$method($value);
        }

        $this->_fields[$name] = $object;
        $trigger = new CallableTrigger(function($event) use($name) {
            return $name == $event->getFieldName();
        });

        // TODO:
        //   If we do this then the field cannot be removed and changes to the
        //   field object will not impact the processing.  We can sort of argue
        //   that fields are supposed to be stateless but this might be pretty
        //   inconvenient.
        foreach ($params['object']->_processing as $process) {
            $this->addProcessing($trigger, $process);
        }
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
        // would also be useful to have states in the message, w ewould only ned
        // to know about the invalid one.
    }

    /**
     * True if the form has data an the data was pprocessed successfully and the
     * form was not otherwise set invalid.  Otherwise false.
     */
    public function isValid()
    {
        return $this->getMessages()->isValid();
    }

    /**
     * Return nessages mapped from fields.
     */
    public function getMessages()
    {
        $this->process();
        return $this->_messages;
    }

    /**
     * Turn the raw data into PHP data, recording validation errors along the
     * way.
     */
    private function process()
    {
        if ($this->_processed !== null) {
            return;
        }

        foreach ($this->_preparation as $preparation) {
            $preparation->execute($this);
        }

        $this->_processed = array();
        $this->_messages = new Messages();
        $this->_state = array();

        // TODO:
        //   a sequence like this won't work for subgroups or lists (obviously)
        //   but we can be recursive easily enough.
        //
        //   additionally, if we could make groups include their own processing
        //   that would make it easier to re-use things.
        //
        //
        //   Interestingly this works OK without us ever adding any fields.
        foreach ($this->getFieldNames() as $field) {
            $this->_loadStack = array();
            $this->load($field);
        }
    }

    const DONE_STATE = 1;
    const INVALID_STATE = 2;
    const SKIP_STATE = 3;

    /**
     * Cause a field to be evaluated.  This is mainly public so it can be used
     * from inside processing.
     */
    public function load($field)
    {
        // We could be loading this field from within another processor.
        if (isset($this->_state[$field])) {
            $state = $this->_state[$field];
            if ($state == self::DONE_STATE) {
                return $this->_processed[$field];
            } else if ($state == self::INVALID_STATE) {
                $this->_loadStack[] = $field;
                $stack = implode(" -> ", $this->_loadStack);
                throw new ValidationException("dependent field is invalid: $stack");
            } else if ($state == self::SKIP_STATE) {
                $this->_loadStack[] = $field;
                $stack = implode(" -> ", $this->_loadStack);
                throw new ValidationException("cannot use skipped field: $stack");
            } else {
                throw new \Exception("unpossible state");
            }
        }

        if (in_array($field, $this->_loadStack)) {
            $this->_loadStack[] = $field;
            throw new \Exception("circular dependency: " . implode(" -> ", $this->_loadStack));
        }

        $this->_loadStack[] = $field;

        $event = new ProcessingEvent();
        $event->_name = $field;

        foreach ($this->_disables as $trigger) {
            if ($trigger->matches($event)) {
                $this->_state[$field] = self::SKIP_STATE;
                $this->_processed[$field] = null;
                return null;
            }
        }

        $value = isset($this->_data[$field]) ? $this->_data[$field] : null;

        $errors = null;
        foreach ($this->_processing as $processing) {
            list($trigger, $process) = $processing;

            if ($trigger->matches($event)) {
                $context = new ProcessingContext();
                $context->_value = $value;
                $context->_form = $this;

                $ex = null;
                try {
                    // TODO:
                    //   the processor should call setValue on the context for
                    //   updates.  that makes it easier to do validation and so
                    //   on.  we can have another class of processors which
                    //   always return a value.
                    $processed = $process->execute($context);
                } catch (ValidationException $ex) {
                    $processed = null;
                }

                list($value, $errors) = $this->toProcessedData($processed, $ex);

                // TODO:
                //   Handle cases where the errors should not cause processing
                //   to stop.
                foreach ($errors as $error) {
                    $this->_messages->add($event->getFieldName(), $error);
                }

                if (! $errors) {
                    break;
                }
            }
        }

        // TODO:
        //   Processors could cause other states to happen.
        if ($errors) {
            $this->_state[$field] = self::INVALID_STATE;
        } else {
            $this->_state[$field] = self::DONE_STATE;
        }

        $this->_processed[$field] = $value;
        return $value;
    }

    private function getFieldNames()
    {
        if ($this->_fields) {
            return array_keys($this->_fields);
        } else {
            // TODO:
            //   this is probably not a good idea bceayse the client controls
            //   these values, also it might cause some problems as far as field
            //   groups go.
            return array_keys($this->_data);
        }
    }

    // TODO:
    //   this needs testing, should probably be a factory somewhere so we can
    //   unit it (though w do need to care about the behaviour of optional
    //   parameters).
    static private function toTrigger($trigger)
    {
        if ($trigger instanceof TriggerInterface) {
            return $trigger;
        } else if (is_string($trigger)) {
            return new CallableTrigger(function($event) use($trigger) {
                return $event->getFieldName() == $trigger;
            });
        } else if (is_callable($trigger)) {
            return new CallableTrigger($trigger);
        } else {
            self::throwTypeError($trigger);
        }
    }

    /**
     * Interpret the return value (or exception) of a processor.
     */
    static private function toProcessedData($value, $ex)
    {
        if ($value instanceof ProcessedData) {
            $processed = $value;
        } else {
            $processed = new ProcessedData($value);
        }

        $errors = array();
        if ($ex) {
            $errors[] = $ex;
        }

        foreach ($processed->errors as $error) {
            $errors[] = $error;
        }

        if ($errors) {
            $value = null;
        } else {
            $value = $processed->value;
        }

        return array($value, $errors);
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
        if ($param instanceof ProcessingInterface) {
            return $param;
        } else if (is_callable($param)) {
            return new CallableProcessing($param);
        } else {
            self::throwTypeError($param);
        }
    }

    static private function toField($param)
    {
        if (is_string($param)) {
            if (class_exists($class = 'Simpliform\Field\\' . ucfirst($param) . "Field")) {
                return new $class;
            } else {
                throw new \Exception("can't find field: $param");
            }
        } else if (is_object($param)) {
            return $param;
        } else {
            self::throwTypeError($param);
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
