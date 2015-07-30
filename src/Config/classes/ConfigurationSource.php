<?php

/**
 * @author pgorbachev
 *
 */
class ConfigurationSource
{
    const PRIORITY_DEFAULT = 0;
    
    protected $key;
    protected $source;
    protected $priority;
    
    public function __construct(\SplFileInfo $source, $priority = self::PRIORITY_DEFAULT)
    {
        $this->source = new \SplFileInfo($source->getPathname());
        $this->priority = (int)$priority;
        $this->key = md5(str_replace('\\', '/', $this->source->getPathname()));
    }
    
    public function getPriority()
    {
        return $this->priority;
    }
    
    public function getSource()
    {
        return $this->source;
    }
    
    /**
     * Get a hash based key that identifies this configuration source.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
    
    public function getLastModified()
    {
        return $this->source->getMTime();
    }
    
    /**
     * Load configuration data from the source.
     *
     * @param ConfigurationLoader $loader
     * @param array<string, mixed> $params
     * @return Configuration
     *
     * @throws ConfigurationLoadingException
     */
    public function loadConfiguration(ConfigurationLoader $loader, array $params = [])
    {
        try
        {
            $data = $loader->findLoader($this->source)->load($this->source, $params);
            	
            return new Configuration($this->createBase($data));
        }
        catch(\Exception $e)
        {
            throw new ConfigurationLoadingException(sprintf('Unable to load config from source "%s"', $this->source->getPathname()), 0, $e);
        }
    }
    
    protected function createBase(array $data)
    {
        $file = pathinfo($this->source->getFilename(), PATHINFO_FILENAME);
    
        if(false === strpos($file, '.'))
        {
            return $this->changeKeyCase($data);
        }
    
        $parts = explode('.', strtolower($file));
        $base = [];
        $current = & $base;
    
        foreach($parts as $key)
        {
            $current[$key] = [];
            $current = & $current[$key];
        }
    
        $current = $this->changeKeyCase($data);
    
        return $base;
    }
    
    protected function changeKeyCase(array $input)
    {
        $result = [];
    
        foreach($input as $k => $v)
        {
            if(is_string($k))
            {
                $k = strtolower($k);
            }
            	
            $result[$k] = is_array($v) ? $this->changeKeyCase($v) : $v;
        }
    
        return $result;
    }
}