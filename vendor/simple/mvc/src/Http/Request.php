<?php

namespace Simple\Http;

/**
 * Request class
 * 
 * It handles the headers and the body of the request
 */
class Request extends \Simple\Http implements \Simple\RequestInterface
{
	/**
	 * Constructor
	 * 
	 * Prepare http input
	 */
	public function __construct()
	{
		$route = \Simple\Application::getInstance()['route']->get();
		$body = http_build_query(($route['query'] ?? []) + $_GET + $_POST);
		$headers = [
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
			'Content-Length: ' . strlen($body)
		];
		
		parent::__construct($body, $headers);
	}

	/** 
	 * Get an input parameter
	 * 
	 * @param int|string $key
	 * @param mixed $default
	 * @return string
	 */
	public function getParam($key, $default = null)
	{
		return $this->getBody()[$key] ?? $default;
	}
	
	/**
	 * Get all input parameters
	 * 
	 * @return array
	 */
	public function getParams()
	{
		return $this->getBody() ?: [];
	}

	/**
	 * Get the request method (Usually GET or POST)
	 * 
	 * @return string
	 */
	public function getMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

}
