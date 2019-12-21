<?php

namespace Json\Config;

use Json\Config;

/**
 * Reads php files and converts them to Json\Config objects.
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
 * use Json\Config\Php;
 *
 * $config = new Php("path/config.php");
 *
 * echo $config->database->username;
 *```
 */
class Php extends Config
{
    /**
     * Json\Config\Php constructor
     */
    public function __construct($filePath)
    {
        parent::__construct(
            require $filePath
        );
    }
}
