<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Http\Response
 */
class HttpResponseJsonTest extends TestCase
{
	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		$data = ['a' => 0, 'b' => 1];
		$responseJson = new \Simple\Http\ResponseJson($data);
		$this->assertTrue($responseJson instanceof \Simple\HTTP\ResponseJson);
	}

	/**
	 * test output()
	 * 
	 * Avoid "Http headers already sent in PHPUnit/Util/Printer.php"
	 * @runInSeparateProcess
	 */
	public function testOutput()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$data = ['a' => 0, 'b' => 1];
		$responseJson = new \Simple\Http\ResponseJson($data);
		ob_start();
		$responseJson->output();
		$output = ob_get_flush();
		$this->assertSame(json_encode($data), $output);
	}

	/**
	 * test output() with exception
	 */
	public function testOutput_exception()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$data = ['a' => 0, 'b' => 1];
		$responseJson = new \Simple\Http\ResponseJson($data);
		$responseJson->setHeader('Content-Type', 'application/octet-stream');
		try {
			$responseJson->output();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
