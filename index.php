<?php
require_once 'config.inc.php';

interface IController {
    
}

class Controller implements IController {
    
    public function get() {
        return 'Hello, мир!';
    }
}

class AController implements IController {

    public function get() {
        return 'Pathc';
    }
}

class MyMarker extends Marker {
    
    public function get() {
        return "My Marker";
    }
    
}

class MyContainerModule extends AbstractContainerModule
{
    public function build(ContainerBuilder $builder)
    {
        $builder->bind(IController::class)
            ->to(Controller::class)
            ->marked(new MyMarker());
    }
}

/**
 *
 * @author pgorbachev
 *        
 */
class MyModule extends AbstractModule
{

    public function getKey()
    {
        return 'MyModule';
    }

    public function loadContainerModules(ContainerModuleLoader $loader)
    {
        $loader->registerModule(new MyContainerModule());
    }    
}

Application::me()->registerModule(new MyModule());
$app = Application::me();

$a = $app(IController::class);

var_dump($a);
