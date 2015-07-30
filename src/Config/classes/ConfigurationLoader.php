<?php

/**
 * Description of ConfigurationLoader
 *
 * @author mushu_000
 */
class ConfigurationLoader {

    /**
     * Registered config loaders.
     * 
     * @var \SplObjectStorage
     */
    protected $loaders;

    public function __construct() {
        $this->loaders = new \SplObjectStorage();
    }

    /**
     * Register a new config loader.
     * 
     * @param ConfigurationLoaderInterface $loader
     */
    public function registerLoader(IConfigurationLoader $loader) {
        $this->loaders->attach($loader);
    }

    /**
     * Picks a config loader for the given file and returns it.
     * 
     * @param \SplFileInfo $source
     * @return ConfigurationLoaderInterface
     * 
     * @throws \OutOfBoundsException When no loader is able to load the given file.
     */
    public function findLoader(\SplFileInfo $source) {
        foreach ($this->loaders as $loader) {
            if ($loader->isSupported($source)) {
                return $loader;
            }
        }

        throw new \OutOfBoundsException(sprintf('No configuration loader found for "%s"', $source->getPathname()));
    }

}
