<?php

namespace JsonTools\Config;

use \ArrayAccess;
use \ArrayIterator;
use \Countable;
use \IteratorAggregate;
use \JsonSerializable;
use \Serializable;
use \Traversable;

class Collection implements
    ArrayAccess,
    Countable,
    IteratorAggregate,
    JsonSerializable,
    Serializable
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    protected $insensitive = true;

    /**
     * @var array
     */
    protected $lowerKeys = [];

    /**
     * Collection constructor.
     */
    public function __construct($data = [], $insensitive = true)
    {
        $this->insensitive = $insensitive;
        $this->init($data);
    }

    /**
     * Magic getter to get an element from the collection
     */
    public function __get($element)
    {
        return $this->get($element);
    }

    /**
     * Magic isset to check whether an element exists or not
     */
    public function __isset($element)
    {
        return $this->has($element);
    }

    /**
     * Magic setter to assign values to an element
     */
    public function __set($element, $value)
    {
        $this->set($element, $value);
    }

    /**
     * Magic unset to remove an element from the collection
     */
    public function __unset($element)
    {
        $this->remove($element);
    }

    /**
     * Clears the internal collection
     */
    public function clear()
    {
        $this->data      = [];
        $this->lowerKeys = [];
    }

    /**
     * Count elements of an object.
     * See [count](https://php.net/manual/en/countable.count.php)
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Get the element from the collection
     */
    public function get($element, $defaultValue = null, $cast = null)
    {
        if ($this->insensitive) {
            $element = strtolower($element);
        }

        if (!isset($this->lowerKeys[$element])) {
            return $defaultValue;
        }
        $key = $this->lowerKeys[$element];
        $value = $this->data[$key];

        if ($cast) {
            settype($value, $cast);
        }

        return $value;
    }

    /**
     * Returns the iterator of the class
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }


    public function getKeys($insensitive = true)
    {
        if ($insensitive) {
            return array_keys($this->lowerKeys);
        } else {
            return array_keys($this->data);
        }
    }

    public function getValues()
    {
        return array_values($this->data);
    }

    /**
     * Get the element from the collection
     */
    public function has($element)
    {
        if ($this->insensitive) {
            $element = strtolower($element);
        }

        return isset($this->lowerKeys[$element]);
    }

    /**
     * Initialize internal array
     */
    public function init($data = [])
    {
        foreach ($data as $key => $value) {
            $this->setData($key, $value);
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * See [jsonSerialize](https://php.net/manual/en/jsonserializable.jsonserialize.php)
     */
    public function jsonSerialize()
    {
        $records = [];

        foreach ($this->data as $key => $value) {
            if (gettype($value) == "object" && method_exists($value, "jsonSerialize")) {
                $records[$key] = $value->{"jsonSerialize"}();
            } else {
                $records[$key] = $value;
            }
        }

        return $records;
    }

    /**
     * Whether a offset exists
     * See [offsetExists](https://php.net/manual/en/arrayaccess.offsetexists.php)
     */
    public function offsetExists($element)
    {
        $element = (string) $element;

        return $this->has($element);
    }

    /**
     * Offset to retrieve
     * See [offsetGet](https://php.net/manual/en/arrayaccess.offsetget.php)
     */
    public function offsetGet($element)
    {
        $element = (string) $element;

        return $this->get($element);
    }

    /**
     * Offset to set
     * See [offsetSet](https://php.net/manual/en/arrayaccess.offsetset.php)
     */
    public function offsetSet($element, $value)
    {
        $element = (string) $element;

        $this->set($element, $value);
    }

    /**
     * Offset to unset
     * See [offsetUnset](https://php.net/manual/en/arrayaccess.offsetunset.php)
     */
    public function offsetUnset($element)
    {
        $element = (string) $element;

        $this->remove($element);
    }

    /**
     * Delete the element from the collection
     */
    public function remove($element)
    {
        if ($this->has($element)) {
            if ($this->insensitive) {
                $element = strtolower($element);
            }

            $data      = $this->data;
            $lowerKeys = $this->lowerKeys;
            $key       = $lowerKeys[$element];

            unset($lowerKeys[$element]);
            unset($data[$key]);

            $this->data      = $data;
            $this->lowerKeys = $lowerKeys;
        }
    }

    /**
     * Set an element in the collection
     */
    public function set($element, $value)
    {
        $this->setData($element, $value);
    }

    /**
     * String representation of object
     * See [serialize](https://php.net/manual/en/serializable.serialize.php)
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * Returns the object in an array format
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Returns the object in a JSON format
     *
     * The default string uses the following options for json_encode
     *
     * `JSON_HEX_TAG`, `JSON_HEX_APOS`, `JSON_HEX_AMP`, `JSON_HEX_QUOT`,
     * `JSON_UNESCAPED_SLASHES`
     *
     * See [rfc4627](https://www.ietf.org/rfc/rfc4627.txt)
     */
    public function toJson($options = 79)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Constructs the object
     * See [unserialize](https://php.net/manual/en/serializable.unserialize.php)
     */
    public function unserialize($serialized)
    {
        $serialized = (string) $serialized;
        $data       = unserialize($serialized);

        $this->init($data);
    }

    /**
     * Internal method to set data
     */
    protected function setData($element, $value)
    {
        $key = (true === $this->insensitive) ? strtolower($element) : $element;

        $this->data[$element]  = $value;
        $this->lowerKeys[$key] = $element;
    }
}
