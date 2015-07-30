<?php

/**
 * @author pgorbachev
 *
 */
abstract class AbstractModule implements \IModule
{

    protected $directory;

    public function __construct()
    {
        $ref = new \ReflectionClass(get_class($this));
        
        $this->directory = dirname(dirname($ref->getFileName()));
    }

    public function getVendor()
    {
        return explode('/', $this->getKey(), 2)[0];
    }

    public function getName()
    {
        return explode('/', $this->getKey(), 2)[1];
    }

    public function getDirectory()
    {
        return $this->directory;
    }

    public function getConfigurationSources($contextName)
    {
        $sources = [];
        
        // Global komponent configuration:
        $dir = $this->directory . DIRECTORY_SEPARATOR . 'config';
        
        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $entry) {
                switch (strtolower($entry->getExtension())) {
                    case 'php':
                    case 'yml':
                        $sources[] = new ConfigurationSource($entry, self::CONFIG_PRIORITY);
                }
            }
        }
        
        // Context-specific komponent configuration:
        $dir = $this->directory . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $contextName;
        
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

    public function getResource($resource)
    {}

    public function loadContainerModules(ContainerModuleLoader $loader)
    {}
    
    // public function loadInstrumentors(InstrumentorLoader $loader) { }
    public function build(ContainerBuilder $builder)
    {}

    public function boot()
    {}
}