<?php

namespace Simple\Command;

/**
 * Similar duties as ControllerAbstract but when in cli
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
abstract class CommandAbstract
{
	/**
	 * Method executed just before the action
	 * 
	 * @param string $action
	 * @return void
	 */
	public function _beforeAction($action)
	{
	}

	/**
	 * Method executed just after the action
	 * 
	 * @param string $action
	 * @return void
	 */
	public function _afterAction($action)
	{
	}

	/**
	 * Handle the command
	 * 
	 * @return \Simple\ResponseInterface
	 */
	abstract public function run();

}
