<?php

namespace JsonDi;

use JsonDi\Config\Collection;
use \Exception;

/**
 * `JsonDi\Config` is designed to simplify the access to, and the use of,
 * configuration data within applications. It provides a nested object property
 * based user interface for accessing this configuration data within application
 * code.
 *
 *```php
 * $config = new \JsonDi\Config(
 *     [
 *         "database" => [
 *             "adapter"  => "Mysql",
 *             "host"     => "localhost",
 *             "username" => "scott",
 *             "password" => "cheetah",
 *             "dbname"   => "test_db",
 *         ],
 *     ]
 * );
 *```
 */
class Config extends Collection
{
    const DEFAULT_PATH_DELIMITER = ".";

    /**
     * @var string
     */
    protected $pathDelimiter = null;

    /**
     * Gets the default path delimiter
     *
     * @return string
     */
    public function getPathDelimiter()
    {
        if (!$this->pathDelimiter) {
            $this->pathDelimiter = self::DEFAULT_PATH_DELIMITER;
        }

        return $this->pathDelimiter;
    }

    /**
     * Merges a configuration into the current one
     *
     *```php
     * $appConfig = new \JsonDi\Config(
     *     [
     *         "database" => [
     *             "host" => "localhost",
     *         ],
     *     ]
     * );
     *
     * $globalConfig->merge($appConfig);
     *```
     */
    public function merge($toMerge)
    {
        if (gettype($toMerge) === "array") {
            $config = new Config($toMerge);
        } elseif (gettype($toMerge) === "object" && ($toMerge instanceof Config)) {
            $config = $toMerge;
        } else {
            throw new Exception("Invalid data type for merge.");
        }

        $source = $this->toArray();
        $target = $config->toArray();
        $result = $this->internalMerge($source, $target);

        $this->clear();
        $this->init($result);

        return $this;
    }

    /**
     * Returns a value from current config using a dot separated path.
     *
     *```php
     * echo $config->path("unknown.path", "default", ".");
     *```
     */
    public function path($path, $defaultValue = null, $delimiter = null)
    {
        if ($this->has($path)) {
            return $this->get($path);
        }

        if (empty($delimiter)) {
            $delimiter = $this->getPathDelimiter();
        }

        $config = clone $this;
        $keys   = explode($delimiter, $path);

        while (!empty($keys)) {
            $key = array_shift($keys);

            if (!$config->has($key)) {
                break;
            }

            if (empty($keys)) {
                return $config->get($key);
            }

            $config = $config->get($key);
            if (empty($config)) {
                break;
            }
        }

        return $defaultValue;
    }

    /**
     * Sets the default path delimiter
     */
    public function setPathDelimiter($delimiter = null)
    {
        $this->pathDelimiter = $delimiter;

        return $this;
    }

    /**
     * Converts recursively the object to an array
     *
     *```php
     * print_r(
     *     $config->toArray()
     * );
     *```
     */
    public function toArray()
    {
        $results = [];
        $data    = parent::toArray();

        foreach ($data as $key => $value) {
            if (gettype($value) === "object" && method_exists($value, "toArray")) {
                $value = $value->toArray();
            }

            $results[$key] = $value;
        }

        return $results;
    }

    /**
     * Performs a merge recursively
     */
    final protected function internalMerge($source, $target)
    {
        foreach ($target as $key => $value) {
            if (gettype($value) === "array" && isset($source[$key])  && gettype($source[$key] === "array")) {
                $source[$key] = $this->internalMerge($source[$key], $value);
            } elseif (gettype($key) === "int") {
                $source[] = $value;
            } else {
                $source[$key] = $value;
            }
        }

        return $source;
    }

    /**
     * Sets the collection data
     */
    protected function setData($element, $value)
    {
        $element = (string) $element;
        $key     = ($this->insensitive) ? mb_strtolower($element) : $element;

        $this->lowerKeys[$key] = $element;

        if (gettype($value) === "array") {
            $data = new Config($value);
        } else {
            $data = $value;
        }

        $this->data[$element]  = $data;
    }
}
