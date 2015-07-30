<?php

/**
 * 
 * @author pgorbachev
 *
 */

class ContainerBuilder
{
	/**
	 * Holds all binding data grouped by type name.
	 * 
	 * @var array
	 */
	protected $bindings = [];
	
	/**
	 * Create (or extend) a binding for the given type.
	 * 
	 * @param string $typeName Fully-qualified name of the bound type.
	 * @return Binding
	 */
	public function bind($typeName)
	{
		if(empty($this->bindings[$typeName]))
		{
			$this->bindings[$typeName] = [
				false,
				[''],
				[]
			];
		}
		
		return new Binding($this->bindings[$typeName]);
	}
	
	/**
	 * Create a DI container from assembled bindings.
	 * 
	 * @param Configuration $config Global config object that is utilized by the container when injection config settings.
	 * @return Container
	 */
	public function build(Configuration $config = NULL)
	{
		return new Container($this->bindings, $config);
	}
}