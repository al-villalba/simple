<?php

namespace Simple\Http;

/**
 * Response class
 * 
 * It handles the headers and the body of the response
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class ResponseJson extends Response implements \Simple\ResponseInterface
{
	/*
	 * Constructor
	 */
	public function __construct()
	{
		$args = func_get_args();
		parent::__construct(...$args);
		$this->setHeader('Content-Type', 'application/json');
	}

	/**
	 * Prepare output and send it to the client
	 * 
	 * @return void
	 */
	public function output()
	{
		// ensure json
		if( $this->getHeader('Content-Type') != 'application/json' ) {
			throw new \Exception('Header Content-Type must be application/json');
		}
		
		return parent::output();
	}

}
