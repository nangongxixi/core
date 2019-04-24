<?php
namespace j\tool;

/**
 * Class ArrayObject
 * @package j\tool
 */
class ArrayObject implements \ArrayAccess, \Countable, \Iterator{
    /**
     * @var []
     */
    protected $info;

    private $_position = 0;
    private $_total;

    /**
     * ArrayObject constructor.
     * @param $info
     */
    public function __construct($info){
        $this->info = $info;
        $this->_total = count($info);
    }

    /**
     * put your comment there...
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->info[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->info[$offset]);
    }

    public function offsetExists($offset) {
        return isset($this->info[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->info[$offset]) ? $this->info[$offset] : null;
    }

    public function toArray(){
        return $this->info;
    }


    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count(){
        return $this->_total;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current(){
        return current($this->info);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next(){
        $this->_position++;
        next($this->info);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key(){
        return key($this->info);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid(){
        return $this->_position < $this->_total;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind(){
        $this->_position = 0;
        reset($this->info);
    }
}