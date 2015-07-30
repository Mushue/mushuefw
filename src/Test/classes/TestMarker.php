<?php

/**
 * @author pgorbachev
 *
 */
class TestMarker extends \Marker
{
    protected static $required = [
        'event' => true
    ];
    
    public $event;
    
    public $priority = 0;
}