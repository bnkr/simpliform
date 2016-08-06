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
