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
        $configLoader = new ConfigurationLoader();
        $configLoader->registerLoader(new PhpConfigurationLoader());
        $this->configuration = new Configuration();
    }

    protected function loadConfigurationSources() {
        $sources = [];
        $dir = $this->getProjectDirectory() . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $entry) {
                switch (strtolower($entry->getExtension())) {
                    case 'php':
                        $sources[] = new ConfigurationSource($entry, KernelInterface::CONFIG_PRIORITY);
                }
            }
        }
    }

    protected function loadConfiguration() {
        $sources = new \SplPriorityQueue();

        $loader = new ConfigurationLoader();
        $loader->registerLoader(new PhpConfigurationLoader());
        $loader->registerLoader(new YamlConfigurationLoader());

        $config = new Configuration();
        $params = $this->getContainerParams();

        foreach ($sources as $source) {
            $config = $config->mergeWith($source->loadConfiguration($loader, $params));
        }

        return $config;
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
