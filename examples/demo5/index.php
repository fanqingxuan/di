<?php
require_once '../../vendor/autoload.php';

use JsonTools\Di;
use JsonTools\Config;

class Test
{
}

$di = new Di;
$di->loadFromPhp('service.php');

var_dump($di->testA);
