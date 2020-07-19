<?php

namespace Simple;

/**
 * Contract that all requests (cgi and cli) must be compliant with
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
interface RequestInterface
{
	/** 
	 * Get an input parameter
	 * 
	 * @param int|string $key
	 * @return string
	 */
	public function getParam($key);
	
	/**
	 * Get all input parameters
	 * 
	 * @return array
	 */
	public function getParams();

	/**
	 * Get the request method (Usually GET or POST)
	 * 
	 * @return string
	 */
	public function getMethod();

}
