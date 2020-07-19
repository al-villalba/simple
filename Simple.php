<?php

/**
 * A shorthand to retrieve the app object
 *
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Simple
{
	/**
	 * Shortcut to access the app
	 * 
	 * @return \Simple\Application
	 */
	public static function app()
	{
		return \Simple\Application::getInstance();
	}

	/**
	 * Shortcut to access the app's request
	 * 
	 * @return \Simple\RequestInterface
	 */
	public static function request()
	{
		return \Simple\Application::getInstance()->request;
	}
}
