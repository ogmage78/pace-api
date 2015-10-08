<?php

namespace Pace;

use Iterator;
use Countable;
use Exception;
use ArrayAccess;
use JsonSerializable;
use OutOfBoundsException;

class KeyCollection implements ArrayAccess, Countable, Iterator, JsonSerializable
{
    /**
     * The keys as returned by a find.
     *
     * @var array
     */
    protected $keys = [];

    /**
     * The model the keys belong to.
     *
     * @var Model
     */
    protected $model;

    /**
     * Cached reads.
     *
     * @var array
     */
    protected $read = [];

    /**
     * Create a new key collection instance.
     *
     * @param Model $model
     * @param array $keys
     */
    public function __construct(Model $model, array $keys)
    {
        $this->model = $model;
        $this->keys = $keys;
    }

    /**
     * Convert this instance to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * Read all of the keys.
     *
     * @return array
     */
    public function all()
    {
        return iterator_to_array($this);
    }

    /**
     * Count the number of keys.
     *
     * @return int
     */
    public function count()
    {
        return count($this->keys);
    }

    /**
     * Read the current key.
     *
     * @return Model
     */
    public function current()
    {
        return $this->read($this->key());
    }

    /**
     * Get the keys which are not present in the supplied keys.
     *
     * @param mixed $keys
     * @return KeyCollection
     */
    public function diff($keys)
    {
        return $this->fresh(array_diff($this->keys, ($keys instanceof self) ? $keys->keys() : (array)$keys));
    }

    /**
     * Read only the first key.
     *
     * @return Model
     */
    public function first()
    {
        $key = reset($this->keys);

        return $this->read($key);
    }

    /**
     * Check if the specified key exists.
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key)
    {
        return in_array($key, $this->keys, true);
    }

    /**
     * Convert this instance to a serializable array.
     *
     * @return array
     */
    function jsonSerialize()
    {
        return $this->all();
    }

    /**
     * Get the current key.
     *
     * @return mixed
     */
    public function key()
    {
        return current($this->keys);
    }

    /**
     * Get all keys.
     *
     * @return array
     */
    public function keys()
    {
        return $this->keys;
    }

    /**
     * Move forward to the next key.
     */
    public function next()
    {
         next($this->keys);
    }

    /**
     * Check if the specified key exists.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Read the specified key.
     *
     * @param mixed $key
     * @return null|Model
     * @throws OutOfBoundsException if the key does not exist.
     */
    public function offsetGet($key)
    {
        if (!$this->has($key)) {
            throw new OutOfBoundsException("The key '$key' does not exist");
        }

        return $this->read($key);
    }

    /**
     * Set the value for the specified key.
     *
     * @param mixed $key
     * @param mixed $value
     * @throws Exception
     */
    public function offsetSet($key, $value)
    {
        $class = get_class($this);

        throw new Exception("Unable to set key '$key': $class is immutable");
    }

    /**
     * Unset the value at the specified index.
     *
     * @param mixed $key
     * @throws Exception
     */
    public function offsetUnset($key)
    {
        $class = get_class($this);

        throw new Exception("Unable to unset key '$key': $class is immutable");
    }

    /**
     * Paginate the keys.
     *
     * @param int $page
     * @param int $perPage
     * @return KeyCollection
     */
    public function paginate($page, $perPage = 25)
    {
        $offset = max($page - 1, 0) * $perPage;

        return $this->slice($offset, $perPage);
    }

    /**
     * Rewind to the first key.
     */
    public function rewind()
    {
        reset($this->keys);
    }

    /**
     * Add a portion of the keys to a new collection.
     *
     * @param int $offset
     * @param int $length
     * @return KeyCollection
     */
    public function slice($offset, $length = null)
    {
        return $this->fresh(array_slice($this->keys, $offset, $length));
    }

    /**
     * Check if the current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->key() !== false;
    }

    /**
     * Create a new key collection instance.
     *
     * @param array $keys
     * @return KeyCollection
     */
    protected function fresh(array $keys)
    {
        return new static($this->model, $keys);
    }

    /**
     * Read the specified key from the model.
     *
     * @param mixed $key
     * @return Model|null
     */
    protected function read($key)
    {
        if ($key === false) {
            return null;
        }

        if (!array_key_exists($key, $this->read)) {
            $this->read[$key] = $this->model->read($key);
        }

        return $this->read[$key];
    }
}