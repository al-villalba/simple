<?php

namespace Simple;

/**
 * RequestFactory class
 * 
 * It creates a request of type cgi or cli
 */
class RequestFactory
{
	public function __construct()
	{
		throw new \Exception('Call RequestFactory::create()');
	}
	
	/**
	 * Create the request object according to the php sapi name.
	 * It accepts the same parameters as Cli\Request or Http\Request
	 * 
	 * @return \Simple\RequestInterface
	 */
	public static function create()
	{
		$args = func_get_args();
		
		$sapiName = Application::getInstance()['sapi_name'] ?? php_sapi_name();
		if( $sapiName == 'cli' ) {
			return new Cli\Request(...$args);
		} else {
			return new Http\Request(...$args);
		}
	}

}
