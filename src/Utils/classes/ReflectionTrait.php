<?php

trait ReflectionTrait
{
	/**
	 * Determine the fully-qualified name of a type-hint for the given param without actually loading typehinted classes.
	 *
	 * @param \ReflectionParameter $param
	 * @return string Hinted typename or NULL when no type-hint is present.
	 */
	protected function getParamType(\ReflectionParameter $param)
	{
		if($param->isArray())
		{
			return 'array';
		}
		
		if($param->isCallable())
		{
			return 'callable';
		}
		
		$m = NULL;
		
		if(defined('HHVM_VERSION'))
		{
			// @codeCoverageIgnoreStart
			$type = $param->getTypehintText();
			
			if('' === trim($type))
			{
				$type = NULL;
			}
			// @codeCoverageIgnoreEnd
		}
		elseif(preg_match("'\[\s*<[^>]+>\s+([a-z_][a-z_0-9]*(?:\s*\\\\\s*[a-z_][a-z_0-9]*)*)'i", (string)$param, $m))
		{
			$type = preg_replace("'\s+'", '', $m[1]);
		}
		else
		{
			$type = NULL;
		}
		
		if($type !== NULL)
		{
			switch(strtolower($type))
			{
				case 'self':
					$ref = $param->getDeclaringFunction();
					
					if($ref instanceof \ReflectionMethod)
					{
						return $ref->getDeclaringClass()->name;
					}
					
					throw new \RuntimeException(sprintf('Unable to resolve "self" in parameter "%s" of function %s', $param->name, $ref->name));
				case 'boolean':
					return 'bool';
				case 'integer':
					return 'int';
			}
			
			return $type;
		}
	
		return NULL;
	}
	
	protected function buildFieldSignature(\ReflectionProperty $prop)
	{
		if($prop->isProtected())
		{
			$code = 'protected ';
		}
		elseif($prop->isPrivate())
		{
			$code = 'private ';
		}
		else
		{
			$code = 'public ';
		}
		
		if($prop->isStatic())
		{
			$code .= 'static ';
		}
		
		$code .= '$' . $prop->name;
		
		$defaults = $prop->getDeclaringClass()->getDefaultProperties();
		
		if(array_key_exists($prop->name, $defaults))
		{
			$code .= ' = ' . $this->buildLiteralCode($defaults[$prop->name]);
		}
		
		return $code;
	}
	
	protected function buildMethodSignature(\ReflectionMethod $method, $skipAbstract = false, $skipDefaultValues = false)
	{
		if($method->isProtected())
		{
			$code = 'protected ';
		}
		elseif($method->isPrivate())
		{
			$code = 'private ';
		}
		else
		{
			$code = 'public ';
		}
	
		if($method->isAbstract())
		{
			if(!$skipAbstract)
			{
				$code .= 'abstract ';
			}
		}
		elseif($method->isFinal())
		{
			$code .= 'final ';
		}
		
		if($method->isStatic())
		{
			$code .= 'static ';
		}
	
		$code .= 'function ';
	
		if($method->returnsReference())
		{
			$code .= '& ';
		}
	
		$code .= $method->getName() . '(';
	
		foreach($method->getParameters() as $i => $param)
		{
			if($i > 0)
			{
				$code .= ', ';
			}
				
			$code .= $this->buildParameterSignature($param, $skipDefaultValues);
		}
	
		$code .= ')';
	
		return $code;
	}
	
	protected function buildParameterSignature(\ReflectionParameter $param, $skipDefaultValues = false)
	{
		$code = '';
		
		$type = $this->getParamType($param);
		
		if($type !== NULL)
		{
			switch($type)
			{
				case 'bool':
				case 'int':
				case 'float':
				case 'string':
				case 'array':
				case 'callable':
					$code .= $type . ' ';
					break;
				default:
					$code .= '\\' . $type . ' ';
			}
		}
		
		if($param->isPassedByReference())
		{
			$code .= '& ';
		}
	
		$code .= '$' . $param->getName();
	
		if($param->isOptional() && !$skipDefaultValues)
		{
			$default = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
			$code .= ' = ' . $this->buildLiteralCode($default);
		}
	
		return $code;
	}
	
	protected function buildLiteralCode($literal)
	{
		if(!is_array($literal))
		{
			return var_export($literal, true);
		}
		
		$code = '[';
		$i = 0;
		
		foreach($literal as $k => $v)
		{
			if($i++ != 0)
			{
				$code .= ', ';
			}
			
			$code .= $this->buildLiteralCode($k) . ' => ' . $this->buildLiteralCode($v);
		}
		
		return $code . ']';
	}
}