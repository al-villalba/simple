<?php

namespace Simple\Controller;

use \Simple\Http\Response as HttpResponse;

/**
 * Base controller mainly for cgi requests. Provide rendering functionality
 * by means of the View.
 */
class Base
{
	/**
	 * App config
	 * @var array
	 */
	protected $_config;

	/**
	 * Application's request
	 * @var \Simple\RequestInterface
	 */
	protected $_request;

	/**
	 * View object that renders templates
	 * @var \Simple\View
	 */
	protected $_view;

	/**
	 * Init object attributes
	 */
	public function __construct()
	{
		$app = \Simple\Application::getInstance();
		$this->_config = $app['config']['app'];
		$this->_request = $app['request'];
		$this->_view = new \Simple\View($this->_config['theme'], static::class);
	}

	/**
	 * Get an input parameter
	 * 
	 * @param int|string $key
	 * @return string
	 */
	public function getParam($key)
	{
		return $this->_request->getParam($key);
	}

	/**
	 * Get all input parameters
	 * 
	 * @return array
	 */
	public function getParams()
	{
		return $this->_request->getParams();
	}

	/**
	 * Get the name of the controller
	 * 
	 * @return string
	 */
	public function getController()
	{
		$route = \Simple\Application::getInstance()['route']->get();

		return $route['controller'] ?? null;
	}

	/**
	 * Get the name of the action
	 * 
	 * @return string
	 */
	public function getAction()
	{
		$route = \Simple\Application::getInstance()['route']->get();

		return $route['action'] ?? null;
	}

	/**
	 * Get the name of the namespace
	 * 
	 * @return string
	 */
	public function getNamespace()
	{
		$route = \Simple\Application::getInstance()['route']->get();

		return $route['namespace'] ?? null;
	}

	/**
	 * Renders a template with local variables
	 * 
	 * @param string $template
	 * @param array $locals
	 * @return \Simple\ResponseInterface
	 * @throws \Exception
	 */
	public function renderFile($template, $locals = [])
	{
		$locals = array_merge(
			array_intersect_key($this->_config,
				['title' => 0, 'description' => 1, 'keywords' => 2]),
			$locals);

		$output = $this->_view->renderFile($template, $locals);

		// return the response valid for both cgi and cli
		return \Simple\ResponseFactory::create($output);
		// Other possible returns are:
		// return new \Simple\Http\Response($output); // cgi only
		// return new \Simple\Cli\Response($output);  // cli only
	}

	/**
	 * 
	 * @param callback $callback
	 * @param array $args
	 * @return \Simple\ResponseInterface
	 */
	public function renderAction($callback, $args = [])
	{
		return call_user_func_array($callback, $args);
	}

	/**
	 * Redirect to $url
	 * 
	 * @param string $url
	 * @return \Simple\Http\Response
	 */
	public function redirect($url)
	{
		$response = new HttpResponse();
		$response->setHeader('Location', $url);

		return $response;
	}

	/**
	 * TODO
	 * 
	 * @param callable|string $goto
	 * @return \Simple\ResponseInterface
	 * @throws \Exception
	 */
	public function requireLogin($goto)
	{
		$app = \Simple\Application::getInstance();

		if( is_callable($goto) ) {
			$app['response'] = $this->renderAction($goto);
		} elseif( is_string($goto) ) {
			$app['response'] = $this->redirect($goto);
		} else {
			throw new \Exception('Unexpected login redirector');
		}
	}

}
