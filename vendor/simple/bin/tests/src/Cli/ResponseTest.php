<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Cli\Response
 */
class CliResponseTest extends TestCase
{
	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		$response = new \Simple\Cli\Response('test');
		$this->assertTrue($response instanceof \Simple\Cli\Response);
		$this->assertTrue($response instanceof \Simple\ResponseInterface);
	}

	/**
	 * test output()
	 */
	public function testOutput()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});
		
		$response = new \Simple\Cli\Response('test');
		ob_start();
		$response->output();
		$output = ob_get_flush();
		$this->assertSame('test', $output);
	}

}
