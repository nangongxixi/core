<?php

namespace j\base;

/**
 * JObject class
 *
 * @package		j.Framework
 * @subpackage 	Base
 */
class JObject implements ConfigurableInterface {

	use ClassNameTrait;

	/**
	 * An array of errors
	 *
	 * @var		array of error messages or JExceptions objects
	 * @access	protected
	 * @since	1.0
	 */
	private $_errors		= array();

	/**
	 * JObject constructor.
	 * @param array $config
	 */
    function __construct($config = array()) {
		if($config && is_array($config)){
			$this->setProperties($config);
		}

		$this->init();
    }

	protected function init(){

	}

    /**
     * @param array $properties
     * @return $this
     */
	public function setProperties( $properties ) {
		$properties = (array) $properties; //cast to an array
		foreach ($properties as $k => $v) {
			$this->$k = $v;
		}
		return $this;
	}


	/**
	 * Get the most recent error message
	 *
	 * @param	integer	$i Option error index
	 * @param	boolean	$toString Indicates if JError objects should return their error message
	 * @return	string|\Exception	Error message
	 * @access	public
	 * @since	1.5
	 */
	function getError($i = null, $toString = true )	{
		// Find the error
		if ( $i === null) {
			// Default, return the last message
			$error = end($this->_errors);
		} elseif ( ! array_key_exists($i, $this->_errors) ) {
			// If $i has been specified but does not exist, return false
			return false;
		} else {
			$error	= $this->_errors[$i];
		}

		return $error;
	}

	/**
	 * Return all errors, if any
	 *
	 * @access	public
	 * @return	array	Array of error messages or JErrors
	 * @since	1.5
	 */
	function getErrors(){
		return $this->_errors;
	}

    /**
     * @param $error
     * @param bool $replace
     */
	function setError($error, $replace = false){
	    if($replace){
            $this->_errors = $replace;
        } else {
            array_push($this->_errors, $error);
        }
	}
}
