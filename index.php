<?php

require_once 'config.inc.php';

interface IBaseController {

    public function action($a);
}

class Controller implements IBaseController {

    public function action($a) {
        return 'defaultAction';
    }

}

class UserController implements IBaseController {

    public function action($a) {
        return 'userAction';
    }

}

// echo Application::me()->bind(IBaseController::class, UserController::class)
//     ->get(IBaseController::class)
//     ->action(1) . PHP_EOL;

/**
 * ***************************************************************************************************
 */
interface IValidator {

    public function validate(IBaseController $a);
}

class OneValidator implements IValidator {

    public $a = 'One validator';

    public function validate(IBaseController $a) {
        return $this->a;
    }

}

class TwoValidator implements IValidator {

    public $a = 'Two validator';

    public function validate(IBaseController $a) {
        return $this->a;
    }

}

Application::me()->bind(IValidator::class);

class PrizeController {

    public function doOrderPrize(stdClass $request) {
        // @TODO not implemented
        $valid = Application::me()->get(IValidator::class)->validate(new Controller());
    }

}

$pOrder = new PrizeController();
$pOrder->doOrderPrize((object) []);



