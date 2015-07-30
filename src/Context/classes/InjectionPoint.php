<?php

/**
 * @author pgorbachev
 *
 */
final class InjectionPoint
{
	/**
	 * 
	 * @var string
	 */
	public $typeName;
	
	/**
	 * 
	 * @var string
	 */
	public $methodName;
	
	
	public function __construct($typeName)
	{
		$this->typeName = (string)$typeName;
	}
}