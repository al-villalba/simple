<?php

namespace Simple\Cli;

/**
 * Request class
 * 
 * It deals with the output of the response
 */
class Response implements \Simple\ResponseInterface
{
	protected $_output;
	
	public function __construct($output)
	{
		$this->_output = $output;
	}
	
	/**
	 * Send output to the stdout
	 * 
	 * @return void
	 */
	public function output()
	{
		echo $this->_output;
	}

}
