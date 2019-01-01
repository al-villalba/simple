<?php

namespace Simple\Controller;

/**
 * Base controller mainly for cgi requests. Provide rendering functionality
 * by means of the View.
 */
class Base
{
	protected $_view;

	/**
	 * Init object attributes
	 */
	public function __construct()
	{
		$theme = \Simple\Application::getInstance()['config']['app']['theme'] ??
			null;
		$this->_view = new \Simple\View($theme, static::class);
	}
	
	/**
	 * Renders a template with local variables
	 * 
	 * @param string $template
	 * @param array $locals
	 * @return \Simple\ResponseInterface
	 * @throws \Exception
	 */
	public function renderFile($template, $locals)
	{
		$output = $this->_view->renderFile($template, $locals);
		
		// return the response valid for both cgi and cli
		return \Simple\ResponseFactory::create($output);
		// Other possible returns are:
		// return new \Simple\Http\Response($output); // cgi only
		// return new \Simple\Cli\Response($output);  // cli only
	}

}
