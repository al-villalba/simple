<?php

namespace Simple\Http;

/**
 * Response class
 * 
 * It handles the headers and the body of the response
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Response extends \Simple\Http implements \Simple\ResponseInterface
{
	/*
	 * Constructor is inherited from \Simple\Http
	 */

	/**
	 * Prepare output and send it to the client
	 * 
	 * @return void
	 */
	public function output()
	{
		$file = '';
		$line = 0;
		if( headers_sent($file, $line) ) {
			throw new \Exception("Http headers already sent in '$file:$line'");
		}
		
		// send headers
		http_response_code($this->getCode());
		foreach( $this->_assoc2headers($this->getHeaders()) as $h ) {
			header($h);
		}
		
		// send body (accordingly encoded)
		echo $this->getBody(self::BODY_ENCODE);
	}

}
