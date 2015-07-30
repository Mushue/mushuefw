<?php

/**
 * Description of Application
 *
 * @author mushu_000
 */
class Application {

    /**
     *
     * @var Container 
     */
    private $container;
    /**
     *
     * @var ContainerBuilder 
     */
    private $builder;
    
    /**
     *
     * @var Configuration 
     */
    private $configuration;
    
    /**
     *
     * @var Application 
     */
    private static $__instance = false;

    /**
     * 
     * @return Container
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * 
     * @return ContainerBuilder
     */
    public function getBuilder() {
        return $this->builder;
    }
    
    /**
     * 
     * @return Application
     */
    public static function me() {
        if (!static::$__instance) {
            static::$__instance = new static();
        }
        return static::$__instance;
    }

    public function __construct() {
        $this->builder = new ContainerBuilder();
        $this->configuration = new Configuration();
    }

    /**
     * 
     * @param string $typeName
     * @return mixed
     */
    public function get($typeName) {
        $this->container = $this->builder->build();
        return $this->container->get($typeName, true);
    }

    /**
     * 
     * @param string $target
     * @param string $to
     * @return \Application
     */
    public function bind($target, $to = null) {
        $bind = $this->builder->bind($target);
        if ($to) {
            $bind->to($to);
        }
        return $this;
    }

}
