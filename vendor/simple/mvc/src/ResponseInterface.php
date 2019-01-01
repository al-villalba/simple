<?php

namespace Simple;

/**
 * Contract that all responses (cgi and cli) must be compliant with
 */
interface ResponseInterface
{
	/**
	 * Send output to the client
	 */
	public function output();
}
