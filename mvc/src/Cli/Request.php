<?php

namespace Simple\Cli;

/**
 * Request class
 * 
 * It handles the input arguments of the request
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Request implements \Simple\RequestInterface
{
	/**
	 * The options from cli passed with dashes '-' or '--'
	 * @var array associative array which keys are strings
	 */
	protected $_options;

	/**
	 * The arguments from cli that are not options
	 * @var array numerical array
	 */
	protected $_args;

	/**
	 * Parsed options
	 * @var array
	 */
	protected $_params;

	/**
	 * Constructor
	 * 
	 * Prepare input arguments
	 */
	public function __construct()
	{
		global $argv;

		// define the options and arguments passed in the command line
		$shorOpts = 'hg:p:';
		$longOpts = ['help', 'get:', 'post:'];
		$optInd = null;
		$this->_options = getopt($shorOpts, $longOpts, $optInd);

		$this->_args = array_slice($argv, $optInd);

		// parse options extracting the parameters defined in them
		$this->_params = [];
		foreach( ['g', 'get', 'p', 'post'] as $opt ) {
			$params = [];
			parse_str($this->_options[$opt] ?? '', $params);
			$this->_params = array_merge(
				$this->_params,
				$params
			);
		}
	}

	/**
	 * Get options from the cli passed with dashes
	 * 
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Get arguments from the cli that are not options
	 * 
	 * @return array
	 */
	public function getArgs()
	{
		return $this->_args;
	}

	/** 
	 * Get an input parameter
	 * 
	 * @param int|string $key
	 * @return string
	 */
	public function getParam($key)
	{
		if( is_integer($key) ) {
			return $this->_args[$key] ?? null;
		}

		return $this->_params[$key] ?? null;
	}

	/**
	 * Get all input parameters
	 * 
	 * @return array
	 */
	public function getParams()
	{
		return array_merge($this->_args, $this->_params);
	}

	/** 
	 * Set a param (only for internal use in broker)
	 * 
	 * @param string $key
	 * @value mixed $value
	 * @return \Simple\RequestInterface
	 */
	public function _setParam($key, $value)
	{
		$this->_params[$key] = $value;

		return $this;
	}

	/**
	 * Get the request method (Usually GET or POST)
	 * 
	 * @return string
	 */
	public function getMethod()
	{
		if( isset($this->_options['post']) || isset($this->_options['p']) ) {
			return 'POST';
		}

		return 'GET';
	}

}
