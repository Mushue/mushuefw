<?php

/**
 * @author pgorbachev
 *
 */
class InjectionPoint
{
    protected $typeName;
    
    protected $methodName;
	
	
    public function __construct($typeName, $methodName)
	{
		$this->typeName = (string)$typeName;
		$this->methodName = (string)$methodName;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getTypeName()
	{
	    return $this->typeName;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getMethodName()
	{
	    return $this->methodName;
	}
}