<?php

/**
 * @author pgorbachev
 *
 */
final class Binding
{
	/**
	 * Index: Shared-flag within the binding data array.
	 * 
	 * @var integer
	 */
	const SHARED = 0;
	
	/**
	 * Index: Factory callback that is being used to instantiate an object instance.
	 * 
	 * @var integer
	 */
	const FACTORY = 1;
	
	/**
	 * Index: Array of decorators to be applied to the generated object instance.
	 * 
	 * @var integer
	 */
	const DECORATORS = 2;
	
	/**
	 * Index: Array of markers attached to the binding.
	 * 
	 * @var integer
	 */
	const MARKERS = 3;
	
	/**
	 * Reference to the binding data in the container builder.
	 * 
	 * @var array
	 */
	protected $binding;
	
	/**
	 * Creates a binding object that holds a reference to the binding data in the container builder.
	 * 
	 * @param array $binding
	 */
	public function __construct(array & $binding)
	{
		$this->binding = & $binding;
	}
	
	/**
	 * Dumps a minimal representation of the binding.
	 * 
	 * @return array
	 */
	public function __debugInfo()
	{
		$info = [
			'shared' => $this->binding[self::SHARED],
			'decorators' => count($this->binding[self::DECORATORS])
		];
		
		if(!empty($this->binding[self::MARKERS]))
		{
			$info['markers'] = array_keys($this->binding[self::MARKERS]);
		}
		
		return $info;
	}
	
	/**
	 * Shared bindings (singletons) re-use the created object instance.
	 * 
	 * @param bool $shared Singleton when true.
	 * @return Binding
	 */
	public function shared($shared)
	{
		$this->binding[self::SHARED] = $shared ? true : false;
		
		return $this;
	}
	
	/**
	 * Binds to the given target type, primarily useful when binding interfaces to an implementation class.
	 * 
	 * @param string $typeName Fully-qualified name of the target type.
	 * @return Binding
	 */
	public function to($typeName)
	{
		$this->binding[self::FACTORY] = [(string)$typeName];
		
		return $this;
	}
	
	/**
	 * Uses the given factory method to create object instances of the bound type.
	 * 
	 * @param callable $factory
	 * @return Binding
	 */
	public function factory(callable $factory)
	{
		$this->binding[self::FACTORY] = [$factory];
		
		return $this;
	}
	
	/**
	 * Adds a decorator that is applied to the created object instance.
	 * 
	 * @param callable $decorator Decorator receives the object instance as first argument and must return an instance of the same type.
	 * @return Binding
	 */
	public function decorate(callable $decorator)
	{
		$this->binding[self::DECORATORS][] = [$decorator];
		
		return $this;
	}
	
	/**
	 * Attaches a marker to the binding.
	 * 
	 * @param string $typeName Fully-qualified name of the marker class.
	 * @param array $data Property values to be passed to the marker instance as it is being created.
	 * @return Binding
	 */
	public function marked($typeName, array $data = [])
	{
		if(empty($this->binding[self::MARKERS]))
		{
			$this->binding[self::MARKERS] = [];
		}
		
		$this->binding[self::MARKERS][(string)$typeName][] = $data;
		
		return $this;
	}
}