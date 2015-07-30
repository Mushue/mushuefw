<?php

/**
 * @author pgorbachev
 *
 */
interface IContainerModule
{
    
    public function build(ContainerBuilder $builder);
    
    public function boot();
}