<?php

namespace Bangpound\Bundle\DrupalBundle;

/**
 * Class Globals
 * @package Bangpound
 */
class Globals implements \ArrayAccess, \Countable
{
    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($GLOBALS[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $GLOBALS[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $GLOBALS[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($GLOBALS[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($GLOBALS);
    }
}
