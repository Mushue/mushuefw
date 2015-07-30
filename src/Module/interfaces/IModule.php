<?php

/**
 * @author pgorbachev
 *
 */
interface IModule extends \IContainerModule
{
    const CONFIG_PRIORITY = 450;
    
    const CONFIG_CONTEXT_PRIORITY = 350;
    
    public function getKey();
    
    public function getVendor();
    
    public function getName();
    
    public function getDirectory();
    
    public function getConfigurationSources($contextName);
    
    public function getResource($resource);   
    
    public function loadContainerModules(ContainerModuleLoader $loader);
    
    //public function loadInstrumentors(InstrumentorLoader $loader) { }    
    
}