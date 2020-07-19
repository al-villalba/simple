<?php

namespace Simple\Cli;

/**
 * Request class
 * 
 * It deals with the output of the response
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Response implements \Simple\ResponseInterface
{
	/**
	 * The command's exit status (0 = ok)
	 * @var int
	 */
	protected $_exitStatus;
	
	/**
	 * Like the body, will be put on stdout
	 * @var string
	 */
	protected $_output;
	
	/**
	 * Constructor
	 * 
	 * @param string $output
	 * @param int $exitStatus
	 */
	public function __construct($output, $exitStatus = 0)
	{
		$this->_exitStatus = $exitStatus;
		$this->_output = $output;
	}
	
	/**
	 * Set _existStatus
	 * 
	 * @param int $status
	 * @return \Simple\Cli\Response
	 */
	public function setExitStatus($status)
	{
		$this->_exitStatus = $status;
		
		return $this;
	}
	
	/**
	 * Set _existStatus
	 * 
	 * @return \Simple\Cli\Response
	 */
//	public function getExitStatus()
//	{
//		return $this->_exitStatus;
//	}
	
	/**
	 * Send output to the stdout and set exit status
	 * 
	 * @return void
	 */
	public function output()
	{
		\Simple::app()->exitStatus = $this->_exitStatus;
		
		if( $this->_exitStatus == 0 ) {
			echo $this->_output;
		} else {
			file_put_contents('php://stderr', $this->_output);
		}
	}

}
