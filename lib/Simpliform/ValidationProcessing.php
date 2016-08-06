<?php
namespace Simpliform;

/**
 * Takes an object which validates the field in context but applies no change to
 * the value given.  This is run as a processor.
 */
class ValidationProcessing implements ProcessingInterface
{
    private $_validation;

    public function __construct(ValidationInterface $validation)
    {
        $this->_validation = $validation;
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
        $errors = $this->_validation->validate($value, $context);

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
