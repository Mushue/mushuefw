<?php

/**
 * Description of Application
 *
 * @author mushu_000
 */
class Application
{

    const CONFIG_PRIORITY = 500;

    const CONFIG_CONTEXT_PRIORITY = 400;

    /**
     *
     * @var string
     */
    protected $projectDirectory;

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
     * @var string
     */
    private $contextName;

    /**
     *
     * @var ModuleLoader
     */
    private $modules;

    /**
     *
     * @var ContainerModuleLoader
     */
    private $containerLoader;

    /**
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     *
     * @return ContainerBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     *
     * @return Application
     */
    public static function me()
    {
        if (! static::$__instance) {
            static::$__instance = new static();
        }
        return static::$__instance;
    }

    public function __construct($contextName = 'development')
    {
        $this->contextName = $contextName;
        
        $this->directory = rtrim(str_replace([
            '\\',
            '/'
        ], DIRECTORY_SEPARATOR, $this->setupRootDirectory()), '/\\');
        
        if ($this->projectDirectory === NULL) {
            $this->projectDirectory = $this->directory;
        }
        
        $this->configuration = $this->loadConfiguration();
        $this->createContainer();
    }

    public function createContainer()
    {
        $builder = new ContainerBuilder();
        $this->builder = $builder;
        $this->modules = new ModuleLoader();
        $this->containerLoader = new ContainerModuleLoader();
        
        $this->registerModules();
    }

    /**
     * Регистрируем все загруженные модули
     * 
     * @return Application
     */
    protected function registerModules()
    {
        foreach ($this->modules->getIterator() as $module) {
            /**
             *
             * @var $module IModule
             */
            $module->loadContainerModules($this->containerLoader);
        }
        
        foreach ($this->containerLoader->getIterator() as $loader) {
            /**
             *
             * @var $loader IContainerModule
             */
            $loader->build($this->builder);
            $loader->boot();
        }       
        return $this;
    }

    /**
     * Регистрируем модуль
     *
     * @param IModule $module            
     * @return Application
     */
    public function registerModule(IModule $module)
    {
        $this->modules->registerModule($module);
        $this->registerModules();
        return $this;
    }

    /**
     *
     * @return string
     */
    protected function setupRootDirectory()
    {
        return ROOT_PATH;
    }

    /**
     * Загружаем источники конфигураций проекта
     *
     * @return array<ConfigurationSource>
     */
    protected function loadConfigurationSources()
    {
        $sources = [];
        
        $dir = $this->getProjectDirectory() . DIRECTORY_SEPARATOR . 'config';
        
        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $entry) {
                switch (strtolower($entry->getExtension())) {
                    case 'php':
                        $sources[] = new ConfigurationSource($entry, self::CONFIG_PRIORITY);
                }
            }
        }
        
        $dir = $this->getProjectDirectory() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $this->contextName;
        
        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $entry) {
                switch (strtolower($entry->getExtension())) {
                    case 'php':
                        $sources[] = new ConfigurationSource($entry, self::CONFIG_CONTEXT_PRIORITY);
                }
            }
        }
        
        return $sources;
    }

    /**
     * Получить директорию проекта
     *
     * @return string
     */
    public function getProjectDirectory()
    {
        return $this->projectDirectory;
    }

    /**
     * Загрузить все конфигурации
     *
     * @return ConfigurationLoader
     */
    protected function loadConfiguration()
    {
        $sources = new \SplPriorityQueue();
        
        $loader = new ConfigurationLoader();
        $loader->registerLoader(new PhpConfigurationLoader());
        
        foreach ($this->loadConfigurationSources() as $source) {
            $sources->insert($source, $source->getPriority());
        }
        
        $config = new Configuration();
        
        foreach ($sources as $source) {
            $config = $config->mergeWith($source->loadConfiguration($loader, $params));
        }
        
        return $config;
    }

    /**
     * 
     * @param string $typeName
     * @param bool $nullable
     */
    public function __invoke($typeName, $nullable = true)
    {
        return $this->get($typeName, $nullable);
    }

    /**
     *
     * @param string $typeName            
     * @return mixed
     */
    protected function get($typeName, $nullable = true)
    {
        $this->container = $this->builder->build();
        $this->container->eachMarked(function(MyMarker $marker, Binding $binding) {
            var_dump($marker, $binding); 
        });
        return $this->container->get($typeName, $nullable);
    }

    /**
     *
     * @param string $target            
     * @param string $to            
     * @return \Application
     */
    protected function bind($target, $to = null)
    {
        $bind = $this->builder->bind($target);
        if ($to) {
            $bind->to($to);
        }
        return $this;
    }
}
