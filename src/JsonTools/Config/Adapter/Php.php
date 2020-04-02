<?php

namespace JsonTools\Config\Adapter;

use JsonTools\Config;

/**
 * Reads php files and converts them to JsonTools\Config objects.
 *
 * Given the next configuration file:
 *
 *```php
 * <?php
 *
 * return [
 *     "database" => [
 *         "adapter"  => "Mysql",
 *         "host"     => "localhost",
 *         "username" => "scott",
 *         "password" => "cheetah",
 *         "dbname"   => "test_db",
 *     ],
 * ];
 *```
 *
 * You can read it as follows:
 *
 *```php
 * use JsonTools\Config\Php;
 *
 * $config = new Php("path/config.php");
 *
 * echo $config->database->username;
 *```
 */
class Php extends Config
{
    /**
     * JsonTools\Config\Php constructor
     */
    public function __construct($filePath)
    {
        parent::__construct(
            require $filePath
        );
    }
}
