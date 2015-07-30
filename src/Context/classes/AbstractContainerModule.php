<?php

/**
 * @author pgorbachev
 *
 */
abstract class AbstractContainerModule implements \IContainerModule
{

    /**
	 * {@inheritdoc}
	 */
	public function build(ContainerBuilder $builder) { }
	
	/**
	 * {@inheritdoc}
	 */
	public function boot() { }
}