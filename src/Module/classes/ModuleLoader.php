<?php

/**
 * @author pgorbachev
 *
 */
class ModuleLoader implements \Countable, \IteratorAggregate
{

    protected $modules = [];
	
	public function count()
	{
		return count($this->modules);
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->modules);
	}
	
	public function isRegistered($key)
	{
		return isset($this->modules[$key]);
	}
	
	public function getModule($key)
	{
		if(empty($this->modules[$key]))
		{
			throw new \OutOfBoundsException(sprintf('Module not registered: "%s"', $key));
		}
		
		return $this->modules[$key];
	}
	
	public function registerModule(IModule $module)
	{
		$this->modules[$module->getKey()] = $module;
	}

}