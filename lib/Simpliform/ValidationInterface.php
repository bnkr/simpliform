<?php
namespace Simpliform;

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
