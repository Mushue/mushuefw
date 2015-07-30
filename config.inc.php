<?php

define("ROOT_PATH", dirname(__FILE__));
define("SRC_PATH", ROOT_PATH . DIRECTORY_SEPARATOR . 'src');
define("CONFIG_PATH", ROOT_PATH . DIRECTORY_SEPARATOR . 'config');

/**
 * Config
 */
require_once 'src/Config/interfaces/IConfigurationLoader.php';
require_once 'src/Config/classes/Configuration.php';
require_once 'src/Config/classes/ConfigurationSource.php';
require_once 'src/Config/classes/ConfigurationLoader.php';
require_once 'src/Config/classes/PhpConfigurationLoader.php';
require_once 'src/Config/exceptions/ConfigurationMergeException.php';
require_once 'src/Config/exceptions/ConfigurationLoadingException.php';

/**
 * Utils
 */
require_once 'src/Utils/classes/ReflectionTrait.php';

/**
 * Context
 */
require_once 'src/Context/interfaces/IContainerModule.php';
require_once 'src/Context/classes/Binding.php';
require_once 'src/Context/classes/InjectionPoint.php';
require_once 'src/Context/classes/Container.php';
require_once 'src/Context/classes/ContainerBuilder.php';
require_once 'src/Context/classes/AbstractContainerModule.php';
require_once 'src/Context/classes/ContainerModuleLoader.php';
require_once 'src/Context/classes/Marker.php';
require_once 'src/Context/classes/ServiceProvider.php';
require_once 'src/Context/exceptions/ContextLookupException.php';

/**
 * Test 
 */
require_once 'src/Test/interfaces/ITestProvider.php';
require_once 'src/Test/classes/TestProvider.php';
require_once 'src/Test/classes/TestMarker.php';

/**
 * Module
 */
require_once 'src/Module/interfaces/IModule.php';
require_once 'src/Module/classes/ModuleLoader.php';
require_once 'src/Module/classes/AbstractModule.php';

/**
 * Application
 */
require_once 'src/Application/classes/Application.php';

