<?php

/**
 * Config
 */
require_once 'src/Config/classes/Configuration.php';
require_once 'src/Config/classes/ConfigurationLoader.php';
require_once 'src/Config/interfaces/IConfigurationLoader.php';
require_once 'src/Config/exceptions/ConfigurationMergeException.php';

/**
 * Utils
 */
require_once 'src/Utils/classes/ReflectionTrait.php';

/**
 * Context
 */
require_once 'src/Context/classes/Binding.php';
require_once 'src/Context/classes/InjectionPoint.php';
require_once 'src/Context/classes/Container.php';
require_once 'src/Context/classes/ContainerBuilder.php';
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
 * Application
 */
require_once 'src/Application/interfaces/Application.php';

