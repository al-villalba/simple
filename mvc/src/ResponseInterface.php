<?php

namespace Simple;

/**
 * Contract that all responses (cgi and cli) must be compliant with
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
interface ResponseInterface
{
	/**
	 * Send output to the client
	 */
	public function output();
}
