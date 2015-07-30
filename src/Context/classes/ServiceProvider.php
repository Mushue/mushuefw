<?php

/**
 * @author pgorbachev
 *
 */
abstract class ServiceProvider
{
	public function configure() { }
	
	public function bind(ContainerBuilder $builder) { }
}