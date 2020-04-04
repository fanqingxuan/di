<?php
require_once '../../vendor/autoload.php';

use JsonDi\Di;
use JsonDi\Config;

class Test
{
}

$di = new Di;
$di->loadFromPhp('service.php');

var_dump($di->testA);
