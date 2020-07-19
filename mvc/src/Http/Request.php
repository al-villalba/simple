<?php

namespace Simple\Http;

/**
 * Request class
 * 
 * It handles the headers and the body of the request
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Request extends \Simple\Http implements \Simple\RequestInterface
{
	/**
	 * All params (get and/or post)
	 * @var array
	 */
	protected $_params;


	/**
	 * Constructor
	 * 
	 * Prepare http input
	 */
	public function __construct()
	{
		// define http body with the request parameters
		$route = \Simple\Application::getInstance()['route']->get();
//		$_request = ($route['query'] ?? []) + $_GET + $_POST;
//		array_walk_recursive($_request, function(&$v) {
//			if( $v === true ) {
//				$v = 'true';
//			}
//			if( $v === false ) {
//				$v = 'false';
//			}
//		});
//		$body = http_build_query($_request);
//		$headers = [
//			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
//			'Content-Length: ' . strlen($body)
//		];

		$body = file_get_contents('php://input');
		if( php_sapi_name() == 'cli' ) {
			$body = empty($_POST) ? null : json_encode($_POST);
			$headers = [
//				'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
				'Content-Type: application/json; charset=UTF-8',
				'Content-Length: ' . strlen($body)
			];
		} else {
			$headers = \getallheaders();
		}

		parent::__construct(empty($body) ? null : $body, $headers);

		$this->_params = [];
		$_request = ($route['query'] ?? []) + $_GET;
		if( $_request ) {
			$this->_params = $_request;
			// TODO: Test this carefully
//			array_walk_recursive($_request, function(&$v) {
//				if( $v === true ) {
//					$v = 'true';
//				}
//				if( $v === false ) {
//					$v = 'false';
//				}
//			});
		}
		$body = $this->getBody();
		if( $body && is_array($body) ) {
			$this->_params = array_merge($body, $this->_params);
		}
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
		$param = $this->getParams()[$key] ?? $default;
		if( is_array($param) ) {
			array_walk_recursive($param, function(&$v) {
				if( is_string($v) ) {
					$v = urldecode($v);
				}
				if( $v === 'true' ) {
					$v = true;
				}
				if( $v === 'false' ) {
					$v = false;
				}
			});
		} elseif (!empty($param)) {
			$param = urldecode($param);
			if( $param === 'true' ) {
				$param = true;
			}
			if( $param === 'false' ) {
				$param = false;
			}
		}
		
		return $param;
	}
	
	/**
	 * Get all input parameters
	 * 
	 * @return array
	 */
	public function getParams()
	{
		$params = $this->_params;
		array_walk_recursive($params, function(&$v) {
			if( is_string($v) ) {
				$v = urldecode($v);
			}
			if( $v === 'true' ) {
				$v = true;
			}
			if( $v === 'false' ) {
				$v = false;
			}
		});
		
		return $params;
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
