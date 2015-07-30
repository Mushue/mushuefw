<?php

/**
 * 
 * @author pgorbachev
 *
 */

abstract class Marker
{
    /**
     * 
     * @param unknown $object
     * @return boolean
     */
    public function isInstance($object)
    {
        return $object instanceof static;
    }
}