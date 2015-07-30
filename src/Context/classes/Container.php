<?php

/**
 * @author pgorbachev
 *
 */
class Container
{
    use ReflectionTrait;
    
    /**
     * 
     * @var array
     */
    protected $bindings = [];    
    protected $shared = [];
    protected $marked = [];
    private static $typeCache = [];
    private static $conCache = [];
    private static $markerCache;
    
    /**
     * 
     * @var Configuration
     */
    protected $config;
    
    /**
     * 
     * @param array $bindings
     * @param Configuration $config
     */
    public function __construct(array $bindings = [], Configuration $config = NULL)
    {
        $this->bindings = $bindings;
        $this->config = $config ?: new Configuration();
    
        if(self::$markerCache === NULL)
        {
            self::$markerCache = new \SplObjectStorage();
        }
    
        $this->shared[Container::class] = $this;
    }
    
    public function __debugInfo()
    {
        $bindings = array_keys($this->bindings);
        $shared = array_keys($this->shared);
    
        sort($bindings);
        sort($shared);
    
        return [
            'bindings' => $bindings,
            'shared' => $shared
        ];
    }
    
    /**
     * 
     * @param string $typeName
     * @param object $object
     * @return object
     */
    public function bindInstance($typeName, $object)
    {
        return $this->shared[$typeName] = $object;
    }
    
    /**
     * 
     * @param string $typeName
     * @param bool $nullable
     * @param string $target
     * @return object:
     */
    public function get($typeName, $nullable = false, $target = NULL, $method = NULL)
    {
        if($typeName === Configuration::class)
        {
            return ($target === NULL) ? $this->config : $this->config->getConfig(str_replace('\\', '.', $target));
        }
    
        if(isset($this->shared[$typeName]))
        {
            return $this->shared[$typeName];
        }
    
        if(!isset($this->bindings[$typeName]))
        {
            return $this->createObject($typeName, $nullable);
        }
    
        $binding = & $this->bindings[$typeName];
    
        // Lazily create a factory closure in case of implementation bindings:
        if(is_string($binding[Binding::FACTORY][0]))
        {            
            if($binding[Binding::FACTORY][0] === '')
            {
                $binding[Binding::FACTORY][0] = function() use($typeName) {
                    return $this->createObject($typeName);
                };
            }
            else
            {
                $type = $binding[Binding::FACTORY][0];
                $binding[Binding::FACTORY][0] = function() use($type) {
                    return $this->createObject($type);
                };
            }
        }
    
        if($binding[Binding::SHARED])
        {
            return $this->shared[$typeName] = $this->createObjectUsingFactory($typeName, $binding[Binding::FACTORY], $binding[Binding::DECORATORS], $nullable);
        }
    
        return $this->createObjectUsingFactory($typeName, $binding[Binding::FACTORY], $binding[Binding::DECORATORS], $nullable, $target, $method);
    }
    
    /**
     * 
     * @param string $typeName
     * @param array $factory
     * @param array $decorators
     * @param bool $nullable
     * @param string $target
     * @throws ContextLookupException
     */
    protected function createObjectUsingFactory($typeName, array & $factory, array & $decorators, $nullable = false, $target = NULL, $method = NULL)
    {
        if(empty($factory[1]))
        {
            if(is_array($factory[0]))
            {
                $factory[1] = new \ReflectionMethod(is_object($factory[0][0]) ? get_class($factory[0][0]) : $factory[0][0], $factory[0][1]);
            }
            else
            {
                $factory[1] = new \ReflectionFunction($factory[0]);
            }
        }    
        
        // Assemble arguments and scope config objects to the bound type:
        $object = $factory[0](...array_map(function($arg) use($typeName) {
            return ($arg === $this->config) ? $arg->getConfig(str_replace('\\', '.', $typeName)) : $arg;
        }, $this->populateArguments($factory[1], [], $target, $method)));
    
            if(!$object instanceof $typeName)
            {
                if($object === NULL && $nullable)
                {
                    return;
                }
                	
                throw new ContextLookupException(sprintf('Factory must return an instance of %s, returned value is %s', $typeName, is_object($object) ? get_class($object) : gettype($object)));
            }
    
            return empty($decorators) ? $object : $this->applyDecorators($typeName, $object, $decorators);
    }
    /**
     * 
     * @param \Closure $callback
     * @throws ContextLookupException
     */
    public function eachMarked(\Closure $callback)
    {
        if(self::$markerCache->contains($callback))
        {
            list($marker, $ref, $count) = self::$markerCache[$callback];
        }
        else
        {
            $ref = new \ReflectionFunction($callback);
            $params = $ref->getParameters();
            $count = count($params);
            	
            if($count < 2)
            {
                throw new ContextLookupException(sprintf('Callback for marker processing must declare at least 2 arguments'));
            }
            	
            try
            {
                $markerType = $params[1]->getClass();
            }
            catch(\ReflectionException $e)
            {
                throw new ContextLookupException(sprintf('Marker class not found: %s', $this->getParamType($params[1])), 0, $e);
            }
            	
            if($markerType === NULL)
            {
                throw new ContextLookupException(sprintf('Argument #2 of marker callback needs to declare a type-hint for the marker'));
            }
            	
            $marker = $markerType->name;
            	
            if(!$markerType->isSubclassOf(Marker::class))
            {
                throw new ContextLookupException(sprintf('Marker implementation %s must extend %s', $marker, Marker::class));
            }
            	
            self::$markerCache[$callback] = [$marker, $ref, $count];
        }
    
        // Search and cache marked types:
        if(!array_key_exists($marker, $this->marked))
        {
            $this->marked[$marker] = [];
            	
            foreach($this->bindings as $typeName => $binding)
            {
                if(isset($binding[Binding::MARKERS][$marker]))
                {
                    foreach($binding[Binding::MARKERS][$marker] as $data)
                    {
                        $this->marked[$marker][] = [$typeName, new $marker($typeName, $data)];
                    }
                }
            }
        }
    
        $args = ($count == 2) ? [] : array_slice($this->populateArguments($ref, [NULL, NULL]), 2);
        $result = [];
    
        foreach($this->marked[$marker] as $entry)
        {
            $result[] = $callback($entry[0], $entry[1], ...$args);
        }
    
        return $result;
    }
    
    /**
     * 
     * @param \ReflectionFunctionAbstract $ref
     * @param array $args
     * @param string $target
     * @throws ContextLookupException
     * @return object
     */
    public function populateArguments(\ReflectionFunctionAbstract $ref, array $args = [], $target = NULL, $method = NULL)
    {        
        foreach((empty($args) ? $ref->getParameters() : array_slice($ref->getParameters(), count($args))) as $param)
        {
            
            try
            {
                $type = $param->getClass();
            }
            catch(\ReflectionException $e)
            {
                if($param->isOptional())
                {
                    $args[] = NULL;
                    	
                    continue;
                }
    
                throw new ContextLookupException(sprintf('Type of argument "%s" not found: %s', $param->name, $this->getParamType($param)), 0, $e);
            }
            
            
            if($type === NULL)
            {
                if($param->isOptional())
                {
                    $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
                    	
                    continue;
                }
    
                throw new ContextLookupException(sprintf('Cannot populate parameter "%s" without a type hint', $param->name));
            }
            
            switch($type->name)
            {
                case Configuration::class:
                    $args[] = $this->config;
                    break;
                case InjectionPoint::class:
                    if($target === NULL)
                    {
                        if(!$param->isOptional())
                        {
                            throw new ContextLookupException(sprintf('Unable to provide injection point without target'));
                        }
                        	
                        $args[] = NULL;
                    }
                    else
                    {
                        $args[] = new InjectionPoint($target, $method);
                    }
                    break;
                default:
                    $args[] = $this->get($type->name);
            }
        }
    
        return $args;
    }
    
    /**
     * 
     * @param string $typeName
     * @param bool $nullable
     * @throws ContextLookupException
     */
    protected function createObject($typeName, $nullable = false)
    {
        $n = strtolower($typeName);
        
    
        if(isset(self::$typeCache[$n]))
        {
            $ref = self::$typeCache[$n];
        }
        else
        {
            try
            {
                $ref = self::$typeCache[$n] = new \ReflectionClass($typeName);
            }
            catch(\ReflectionException $e)
            {
                if($nullable)
                {
                    return;
                }
    
                throw new ContextLookupException(sprintf('Cannot load type: %s', $typeName), 0, $e);
            }
        }
        
        
    
        if(!$ref->isInstantiable())
        {
            if($ref->isInterface()) {
                $className = uniqid($typeName  . '_temporary', false);
                $implementation = new \ReflectionClass($typeName);
                $tempClass = "class $className implements $typeName { ";
                foreach ($implementation->getMethods() as $method) {
                    $refMethod = new ReflectionMethod($typeName, $method->getName());
                    /**
                     * @var ReflectionParameter $param
                    */
                    $params = array();
                    foreach($refMethod->getParameters() as $param) {
                        $params[] = $this->buildParameterSignature($param);
                    }
                    $tempClass .= " function " . $refMethod->getName() . " (" . join(", ", $params) . ") {  }\n";
                }
                $tempClass .= " };";
                eval($tempClass);
                $typeName = $className;
                $ref = new \ReflectionClass($typeName);
            } else 
            throw new ContextLookupException(sprintf('Type is not instantiable: %s', $typeName));
        }
    
        if(isset(self::$conCache[$n]))
        {
            $con = self::$conCache[$n];
        }
        else
        {
            $con = self::$conCache[$n] = $ref->getConstructor() ?: false;
        }
    
        return ($con === false) ? new $typeName() : new $typeName(...$this->populateArguments($con));
    }
    
    /**
     * 
     * @param string $typeName
     * @param object $object
     * @param array $decorators
     * @throws ContextLookupException
     */
    protected function applyDecorators($typeName, $object, array & $decorators)
    {
        foreach($decorators as $decorator)
        {
            if(empty($decorator[1]))
            {
                if(is_array($decorator[0]))
                {
                    $decorator[1] = new \ReflectionMethod(is_object($decorator[0][0]) ? get_class($decorator[0][0]) : $decorator[0][0], $decorator[0][1]);
                }
                elseif(is_object($decorator[0]) && !$decorator[0] instanceof \Closure)
                {
                    $decorator[1] = new \ReflectionMethod(get_class($decorator[0]), '__invoke');
                }
                else
                {
                    $decorator[1] = new \ReflectionFunction($decorator[0]);
                }
            }
            	
            $object = $decorator[0](...$this->populateArguments($decorator[1], [$object]));
    
            if(!$object instanceof $typeName)
            {
                throw new ContextLookupException(sprintf('Decorator must return an instance of %s, returned value is %s', $typeName, is_object($object) ? get_class($object) : gettype($object)));
            }
        }
    
        return $object;
    }
}