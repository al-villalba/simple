<?php

namespace Simple;

/**
 * Contract that all requests (cgi and cli) must be compliant with
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

}
