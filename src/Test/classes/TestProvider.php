<?php

/**
 * @author pgorbachev
 *
 */
class TestProvider implements ITestProvider
{
    protected $arg;
    
    public function getName() {
        return "Petya";
    }    
    
    public function __construct(\stdClass $arg)
    {
        $this->arg = $arg;
    }
    
    public function getArgument()
    {
        return $this->arg;
    }
}