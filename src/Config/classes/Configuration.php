<?php

/**
 * 
 * @author pgorbachev
 *
 */
class Configuration implements \JsonSerializable, \Serializable, \IteratorAggregate
{
	/**
	 * Config data.
	 * 
	 * @var array<string, mixed>
	 */
	protected $data = [];
	
	/**
	 * Construct a new config object, accepts array data or an instance of Configuration.
	 * 
	 * @param mixed $data
	 * 
	 * @throws \InvalidArgumentException When the given data could not be converted into an array.
	 */
	public function __construct($data = NULL)
	{
		if($data !== NULL)
		{
			if(is_array($data))
			{
				$this->data = $data;
			}
			elseif($data instanceof self)
			{
				$this->data = $data->data;
			}
			else
			{
				throw new \InvalidArgumentException(sprintf('Unsupported config data: %s', is_object($data) ? get_class($data) : gettype($data)));
			}
		}
	}
	
	/**
	 * Will dumpt the config into a YAML string for good readability.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
	}
	
	public static function __set_state(array $data)
	{
		return new static($data['data']);
	}
	
	public function serialize()
	{
		return serialize($this->data);
	}
	
	public function jsonSerialize()
	{
		return $this->data;
	}
	
	public function unserialize($serialized)
	{
		$this->data = unserialize($serialized);
	}
	
	/**
	 * Sort the config array by key (will modify the internal config data).
	 */
	public function sortByKey()
	{
		ksort($this->data);
	}
	
	public function getIterator()
	{
		foreach($this->data as $k => $v)
		{
			yield $k => is_array($v) ? new Configuration($v) : $v;
		}
	}
	
	public function toArray()
	{
		return $this->data;
	}
	
	public function toString($indent = 0)
	{
		$buffer = '';
		
		foreach($this->data as $key => $value)
		{
			$buffer .= str_repeat('  ', $indent) . $key . ':';
			
			if(is_array($value))
			{
				$tmp = new static($value);
				$buffer .= "\n" . $tmp->toString($indent + 1);
			}
			elseif(is_bool($value))
			{
				$buffer .= ' ' . ($value ? 'true' : 'false') . "\n";
			}
			elseif(is_string($value))
			{
				$buffer .= ' ' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
			}
			else
			{
				$buffer .= ' ' . $value . "\n";
			}
		}
		
		return $buffer;
	}
	
	/**
	 * Check if the given key is present in this configuration.
	 * 
	 * @param string $key
	 * @return boolean
	 */
	public function has($key)
	{
		return array_key_exists(strtolower($key), $this->data);
	}
	
	/**
	 * Get the value of the given key (most likely a string).
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 * 
	 * @throws \OutOfBoundsException When the key was not found and no default value is given.
	 */
	public function get($key)
	{
		$resolved = false;
		$value = $this->getByKey($key, $this->data, $resolved);
		
		if($resolved)
		{
			return $value;
		}
		
		if(func_num_args() > 1)
		{
			return func_get_arg(1);
		}
		
		throw new \OutOfBoundsException('Configuration setting not found: "' . $key . '"');
	}
	
	/**
	 * Get a boolean value by key.
	 * 
	 * @param string $key
	 * @param boolean $default
	 * @return boolean
	 * 
	 * @throws \OutOfBoundsException When the key was not found and no default value is given.
	 */
	public function getBoolean($key)
	{
		$resolved = false;
		$value = $this->getByKey($key, $this->data, $resolved);
		
		if($resolved)
		{
			return $value ? true : false;
		}
		
		if(func_num_args() > 1)
		{
			return func_get_arg(1);
		}
		
		throw new \OutOfBoundsException('Configuration setting not found: "' . $key . '"');
	}
	
	/**
	 * Get an integer value by key.
	 * 
	 * @param string $key
	 * @param integer $default
	 * @return integer
	 * 
	 * @throws \OutOfBoundsException When the key was not found and no default value is given.
	 */
	public function getInteger($key)
	{
		$resolved = false;
		$value = $this->getByKey($key, $this->data, $resolved);
		
		if($resolved)
		{
			return (int)$value;
		}
		
		if(func_num_args() > 1)
		{
			return func_get_arg(1);
		}
		
		throw new \OutOfBoundsException('Configuration setting not found: "' . $key . '"');
	}
	
	/**
	 * Get a floating point value by key.
	 * 
	 * @param string $key
	 * @param float $default
	 * @return float
	 * 
	 * @throws \OutOfBoundsException When the key was not found and no default value is given.
	 */
	public function getFloat($key)
	{
		$resolved = false;
		$value = $this->getByKey($key, $this->data, $resolved);
		
		if($resolved)
		{
			return floatval($value);
		}
		
		if(func_num_args() > 1)
		{
			return func_get_arg(1);
		}
		
		throw new \OutOfBoundsException('Configuration setting not found: "' . $key . '"');
	}
	
	/**
	 * Get a string value by key.
	 * 
	 * @param string $key
	 * @param string $default
	 * @return string
	 * 
	 * @throws \OutOfBoundsException When the key was not found and no default value is given.
	 */
	public function getString($key)
	{
		$resolved = false;
		$value = $this->getByKey($key, $this->data, $resolved);
		
		if($resolved)
		{
			return (string)$value;
		}
		
		if(func_num_args() > 1)
		{
			return func_get_arg(1);
		}
		
		throw new \OutOfBoundsException('Configuration setting not found: "' . $key . '"');
	}
	
	/**
	 * Get an array value by key.
	 * 
	 * @param string $key
	 * @param array $default
	 * @return array
	 */
	public function getArray($key, array $default = [])
	{
		$key = strtolower($key);
		
		if(array_key_exists($key, $this->data))
		{
			if(is_array($this->data[$key]))
			{
				return array_values($this->data[$key]);
			}
				
			return (array)$this->data[$key];
		}
		
		return $default;
	}
	
	/**
	 * Count the number of keys within an array / map entry, will return 1 if the entry is a
	 * scalar type, return 0 when no such key exists.
	 * 
	 * @param string $key
	 * @return integer
	 */
	public function getCount($key)
	{
		$key = strtolower($key);
		
		if(array_key_exists($key, $this->data))
		{
			if(is_array($this->data[$key]))
			{
				return count($this->data[$key]);
			}
			
			return 1;
		}
		
		return 0;
	}
	
	/**
	 * Get a configuration object for the given key.
	 * 
	 * @param string $key
	 * @param Configuration $default
	 * @return Configuration
	 */
	public function getConfig($key, Configuration $default = NULL)
	{
		$resolved = false;
		$value = $this->getByKey($key, $this->data, $resolved);
		
		if($resolved)
		{
			return new static($value);
		}
		
		return ($default === NULL) ? new static() : $default;
	}

	/**
	 * Merges this configuration with the given configuration and returns the
	 * result as a new configuration object.
	 * 
	 * @param Configuration $container
	 * @return Configuration
	 * 
	 * @throws ConfigurationMergeException
	 */
	public function mergeWith(Configuration $container)
	{
		return $this->mergeWithConfiguration($container);
	}
	
	protected function mergeWithConfiguration(Configuration $container, array $keyStack = [])
	{
		$result = [];
		
		foreach($container->data as $key => $value)
		{
			if(!array_key_exists($key, $this->data))
			{
				$result[$key] = $value;
				
				continue;
			}
			
			if(is_array($this->data[$key]) && is_array($value))
			{
				reset($this->data[$key]);
				reset($value);
				
				$fk1 = key($this->data[$key]);
				$fk2 = key($value);
				
				if($fk1 === NULL)
				{
					$result[$key] = $value;
				}
				elseif($fk2 === NULL)
				{
					$result[$key] = $this->data[$key];
				}
				elseif(is_string($fk1) && is_string($fk2))
				{
					$result[$key] = (new static($this->data[$key]))->mergeWithConfiguration(new static($value), array_merge($keyStack, [$key]))->toArray();
				}
				elseif(is_integer($fk1) && is_integer($fk2))
				{
					$result[$key] = array_values($this->data[$key]);
					
					foreach($value as $v)
					{
						$result[$key][] = $v;
					}
				}
				else
				{
					throw new ConfigurationMergeException(sprintf('Cannot merge list and map for key "%s"', implode('.', array_merge($keyStack, [$key]))));
				}
			}
			elseif(!is_array($this->data[$key]) && !is_array($value))
			{
				$result[$key] = $value;
			}
			else
			{
				throw new ConfigurationMergeException(sprintf('Incompatible merge types for key "%s": %s and %s', implode('.', array_merge($keyStack, [$key])), gettype($this->data[$key]), gettype($value)));
			}
		}
		
		foreach($this->data as $key => $value)
		{
			if(!array_key_exists($key, $result))
			{
				$result[$key] = $value;
			}
		}
		
		return new static($result);
	}
	
	protected function getByKey($key, array $data, & $resolved)
	{
		foreach(explode('.', strtolower($key)) as $keyPart)
		{
			if(is_array($data) && array_key_exists($keyPart, $data))
			{
				$data = $data[$keyPart];
			}
			else
			{
				$resolved = false;
				
				return;
			}
		}
		
		$resolved = true;
		
		return $data;
	}
}