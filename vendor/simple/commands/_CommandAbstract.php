<?php

namespace Simple\Command;

abstract class _CommandAbstract
{
	/**
	 * Method executed just before the action
	 * 
	 * @param string $actionName
	 * @return void
	 */
	public function _before($actionName)
	{
	}

	/**
	 * Method executed just after the action
	 * 
	 * @param string $actionName
	 * @return void
	 */
	public function _after($actionName)
	{
	}

	/**
	 * Handle the command
	 * 
	 * @return \Simple\ResponseInterface
	 */
	abstract public function run();

}
