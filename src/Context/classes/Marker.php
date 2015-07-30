<?php

/**
 * 
 * @author pgorbachev
 *
 */

abstract class Marker
{
	/**
	 * An array holding all the properties of the marker that are required.
	 * 
	 * Keys are property names, value must be set to true.
	 * 
	 * @var array
	 */
	protected static $required = [];
	
	/**
	 * Cached public property names grouped by marker type name.
	 * 
	 * @var array
	 */
	private static $propCache = [];
	
	/**
	 * Constructor should only be used by the DI container, do not instantiate markers yourself.
	 * 
	 * @param string $typeName Name of the bound type that was marked.
	 * @param array $data All property values to be passed to the marker.
	 * 
	 * @throws ContextLookupException When a required marker property is missing.
	 */
	public final function __construct($typeName, array $data)
	{
		if(!isset(self::$propCache[static::class]))
		{
			self::$propCache[static::class] = \Closure::bind(function($marker) {
				return array_keys(get_object_vars($marker));
			}, NULL, NULL)->__invoke($this);
		}
		
		foreach(self::$propCache[static::class] as $k)
		{
			if(array_key_exists($k, $data))
			{
				$this->$k = $data[$k];
			}
			elseif(isset(static::$required[$k]))
			{
				throw new ContextLookupException(sprintf('Missing required property "%s" of marker %s at binding %s', $k, static::class, $typeName));
			}
		}
	}
}