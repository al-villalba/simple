<?php

namespace Simple\Controller;

class Homepage extends _ControllerAbstract
{
	/**
	 * Handle request Homepage/index
	 * @return \Simple\ResponseInterface
	 */
	public function index()
	{
		$locals = [
			'method' => __METHOD__ . "\n"
		];
		
		return $this->renderFile('homepage.phtml', $locals);
		
		// return response valid for both cgi and cli
//		return \Simple\ResponseFactory::create($output);
		// Other possible returns are:
		// return new \Simple\Http\Response($output); // cgi only
		// return new \Simple\Cli\Response($output);  // cli only
	}

}
