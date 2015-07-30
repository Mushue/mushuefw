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
        
        var_dump($bindings);
        
        $this->config = $config ?  : new Configuration();
        
        if (self::$markerCache === NULL) {
            self::$markerCache = new \SplObjectStorage();
        }
        
        $this->registerBindings($this->bindings);
        
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
    public function gets($typeName, InjectionPoint $point = NULL)
    {
        if ($typeName === Configuration::class) {
            return ($target === NULL) ? $this->config : $this->config->getConfig(str_replace('\\', '.', $point->getTypeName()));
        }
        
        if (isset($this->shared[$typeName])) {
            return $this->shared[$typeName];
        }
        
        if (! isset($this->bindings[$typeName])) {
            return $this->createObject($typeName);
        }
        
        $binding = & $this->bindings[$typeName];
        
        // Lazily create a factory closure in case of implementation bindings:
        if (is_string($binding[Binding::FACTORY][0])) {
            if ($binding[Binding::FACTORY][0] === '') {
                $binding[Binding::FACTORY][0] = function () use($typeName) {
                    return $this->createObject($typeName);
                };
            } else {
                $type = $binding[Binding::FACTORY][0];
                $binding[Binding::FACTORY][0] = function () use($type) {
                    return $this->createObject($type);
                };
            }
        }
        
        if ($binding[Binding::SHARED]) {
            //return $this->shared[$typeName] = $this->createObjectUsingFactory($typeName, $binding[Binding::FACTORY], $binding[Binding::DECORATORS], $point);
            return $this->shared[$typeName] = $this->getBound(new Binding($binding), $point);
        }
        
        //return $this->createObjectUsingFactory($typeName, $binding[Binding::FACTORY], $binding[Binding::DECORATORS], $point);
        return $this->getBound(new Binding($binding), $point);
    }
    
    public function getBound(Binding $binding, InjectionPoint $point = NULL)
    {
        foreach($binding->getBinding() as $typeBinding => $bind) {
            switch($typeBinding) {
                case Binding::FACTORY:
                        return $this->createObjectUsingFactory($binding, $point);
                    break;
                default:
                    return $this->get($binding->getTarget(), $point);
                    break;
            }
        }
        
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
        if (empty($factory[1])) {
            if (is_array($factory[0])) {
                $factory[1] = new \ReflectionMethod(is_object($factory[0][0]) ? get_class($factory[0][0]) : $factory[0][0], $factory[0][1]);
            } else {
                $factory[1] = new \ReflectionFunction($factory[0]);
            }
        }
        
        // Assemble arguments and scope config objects to the bound type:
        $object = $factory[0](...array_map(function ($arg) use($typeName) {
            return ($arg === $this->config) ? $arg->getConfig(str_replace('\\', '.', $typeName)) : $arg;
        }, $this->populateArguments($factory[1], [], $target, $method)));
        
        if (! $object instanceof $typeName) {
            if ($object === NULL && $nullable) {
                return;
            }
            
            throw new ContextLookupException(sprintf('Factory must return an instance of %s, returned value is %s', $typeName, is_object($object) ? get_class($object) : gettype($object)));
        }
        
        return empty($decorators) ? $object : $this->applyDecorators($typeName, $object, $decorators);
    }

    protected function registerBindings(array $bindings)
    {
        foreach ($this->bindings as $typeName => $binding) {
            foreach ($binding[Binding::MARKERS] as $marker) {
                $markerClass = get_class($marker);
                
                if (empty($this->markers[$markerClass])) {
                    $this->markers[$markerClass] = new \SplObjectStorage();
                }
                $this->markers[$markerClass]->attach(new Binding($binding));
            }
        }
    }
    
    /**
     *
     * @param callable $callback            
     * @return array
     */
    public function eachMarked(callable $callback)
    {
        $ref = new \ReflectionFunction($callback);
        $type = $ref->getParameters()[0]->getClass();
        
        if (empty($type->name)) {
            return [];
        }
        
        if (empty($this->markers[$type->name])) {
            return [];
        }
        
        $result = [];
        
        foreach ($this->markers[$type->name] as $binding) {
            foreach ($binding->getMarkers() as $marker) {
                if ($type->isInstance($marker)) {
                    $result[] = $callback($marker, $binding);
                }
            }
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
        foreach ((empty($args) ? $ref->getParameters() : array_slice($ref->getParameters(), count($args))) as $param) {
            
            try {
                $type = $param->getClass();
            } catch (\ReflectionException $e) {
                if ($param->isOptional()) {
                    $args[] = NULL;
                    
                    continue;
                }
                
                throw new ContextLookupException(sprintf('Type of argument "%s" not found: %s', $param->name, $this->getParamType($param)), 0, $e);
            }
            
            if ($type === NULL) {
                if ($param->isOptional()) {
                    $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : NULL;
                    
                    continue;
                }
                
                throw new ContextLookupException(sprintf('Cannot populate parameter "%s" without a type hint', $param->name));
            }
            
            switch ($type->name) {
                case Configuration::class:
                    $args[] = $this->config;
                    break;
                case InjectionPoint::class:
                    if ($target === NULL) {
                        if (! $param->isOptional()) {
                            throw new ContextLookupException(sprintf('Unable to provide injection point without target'));
                        }
                        
                        $args[] = NULL;
                    } else {
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
        
        if (isset(self::$typeCache[$n])) {
            $ref = self::$typeCache[$n];
        } else {
            try {
                $ref = self::$typeCache[$n] = new \ReflectionClass($typeName);
            } catch (\ReflectionException $e) {
                if ($nullable) {
                    return;
                }
                
                throw new ContextLookupException(sprintf('Cannot load type: %s', $typeName), 0, $e);
            }
        }
        
        if (! $ref->isInstantiable()) {
            if ($ref->isInterface()) {
                $className = uniqid($typeName . '_temporary', false);
                $implementation = new \ReflectionClass($typeName);
                $tempClass = "class $className implements $typeName { ";
                foreach ($implementation->getMethods() as $method) {
                    $refMethod = new ReflectionMethod($typeName, $method->getName());
                    /**
                     *
                     * @var ReflectionParameter $param
                     */
                    $params = array();
                    foreach ($refMethod->getParameters() as $param) {
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
        
        if (isset(self::$conCache[$n])) {
            $con = self::$conCache[$n];
        } else {
            $con = self::$conCache[$n] = $ref->getConstructor() ?  : false;
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
        foreach ($decorators as $decorator) {
            if (empty($decorator[1])) {
                if (is_array($decorator[0])) {
                    $decorator[1] = new \ReflectionMethod(is_object($decorator[0][0]) ? get_class($decorator[0][0]) : $decorator[0][0], $decorator[0][1]);
                } elseif (is_object($decorator[0]) && ! $decorator[0] instanceof \Closure) {
                    $decorator[1] = new \ReflectionMethod(get_class($decorator[0]), '__invoke');
                } else {
                    $decorator[1] = new \ReflectionFunction($decorator[0]);
                }
            }
            
            $object = $decorator[0](...$this->populateArguments($decorator[1], [
                $object
            ]));
            
            if (! $object instanceof $typeName) {
                throw new ContextLookupException(sprintf('Decorator must return an instance of %s, returned value is %s', $typeName, is_object($object) ? get_class($object) : gettype($object)));
            }
        }
        
        return $object;
    }
}