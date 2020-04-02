<?php

namespace JsonTools\Config\Adapter;

use JsonTools\Config;
use \Exception;

/**
 * Reads ini files and converts them to JsonTools\Config objects.
 *
 * Given the next configuration file:
 *
 *```ini
 * [database]
 * adapter = Mysql
 * host = localhost
 * username = scott
 * password = cheetah
 * dbname = test_db
 * ```
 *
 * You can read it as follows:
 *
 *```php
 * use JsonTools\Config\Ini;
 *
 * $config = new Ini("path/config.ini");
 *
 * echo $config->database->username;
 *```
 *
 * PHP constants may also be parsed in the ini file, so if you define a constant
 * as an ini value before calling the constructor, the constant's value will be
 * integrated into the results. To use it this way you must specify the optional
 * second parameter as `INI_SCANNER_NORMAL` when calling the constructor:
 *
 * ```php
 * $config = new \JsonTools\Config\Ini(
 *     "path/config-with-constants.ini",
 *     INI_SCANNER_NORMAL
 * );
 * ```
 */
class Ini extends Config
{
    /**
     * Ini constructor.
     */
    public function __construct($filePath, $mode = null)
    {
        // Default to INI_SCANNER_RAW if not specified
        if (null === $mode) {
            $mode = INI_SCANNER_RAW;
        }

        $iniConfig = parse_ini_file($filePath, true, $mode);

        if ($iniConfig === false) {
            throw new Exception(
                "Configuration file " . basename($filePath) . " cannot be loaded"
            );
        }

        $config = [];

        foreach ($iniConfig as $section => $directives) {
            if (gettype($directives) === "array") {
                $sections = [];

                foreach ($directives as $path => $lastValue) {
                    $sections[] = $this->parseIniString(
                        (string) $path,
                        $lastValue
                    );
                }

                if (count($sections)) {
                    $config[$section] = call_user_func_array(
                        "array_replace_recursive",
                        $sections
                    );
                }
            } else {
                $config[$section] = $this->cast($directives);
            }
        }

        parent::__construct($config);
    }

    /**
     * We have to cast values manually because parse_ini_file() has a poor
     * implementation.
     */
    protected function cast($ini)
    {
        if (gettype($ini) === "array") {
            foreach ($ini as $key => $value) {
                $ini[$key] = $this->cast($value);
            }

            return $ini;
        }

        // Decode true
        $ini      = (string) $ini;
        $lowerIni = strtolower($ini);

        switch ($lowerIni) {
            case "true":
            case "yes":
            case "on":
                return true;
            case "false":
            case "no":
            case "off":
                return false;
            case "null":
                return null;
        }

        // Decode float/int
        if (gettype($ini) === "string" && is_numeric($ini)) {
            if (preg_match("/[.]+/", $ini)) {
                return (double) $ini;
            } else {
                return (int) $ini;
            }
        }

        return $ini;
    }

    /**
     * Build multidimensional array from string
     */
    protected function parseIniString($path, $value)
    {
        $value    = $this->cast($value);
        $position = strpos($path, ".");

        if (false === $position) {
            return [
                $path => $value
            ];
        }

        $key  = substr($path, 0, $position);
        $path = substr($path, $position + 1);

        return [
            $key => $this->parseIniString($path, $value)
        ];
    }
}
