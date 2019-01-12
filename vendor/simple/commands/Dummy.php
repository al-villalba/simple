<?php

namespace Simple\Command;

use \Simple\Cli\Response;

class Dummy extends _CommandAbstract
{
	/**
	 * Handle command "dummy"
	 * 
	 * @return \Simple\ResponseInterface
	 */
	public function run()
	{
		$output = __METHOD__ . "\n";

		return new Response($output);
	}

}
