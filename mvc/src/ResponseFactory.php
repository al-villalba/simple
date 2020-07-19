<?php

namespace Simple;

/**
 * ResponseFactory class
 * 
 * It creates a response of type cgi or cli
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class ResponseFactory
{
	public function __construct()
	{
		throw new \Exception('Call ResponseFactory::create()');
	}
	
	/**
	 * Create the response object according to the php sapi name.
	 * It accepts the same parameters as Cli\Response or Http\Response
	 * 
	 * @return \Simple\ResponseInterface
	 */
	public static function create()
	{
		$args = func_get_args();
		
		$sapiName = Application::getInstance()['sapi_name'] ?? php_sapi_name();
		if( $sapiName == 'cli' ) {
			return new Cli\Response(...$args);
		} else {
			return new Http\Response(...$args);
		}
	}

	/**
	 * Alias of create
	 */
	public static function factory(...$args)
	{
		return static::create($args);
	}

}
