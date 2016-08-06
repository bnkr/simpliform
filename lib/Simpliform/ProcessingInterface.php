<?php
namespace Simpliform;

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
