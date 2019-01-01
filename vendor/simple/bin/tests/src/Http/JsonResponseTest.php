<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Http\Response
 */
class HttpJsonResponseTest extends TestCase
{
	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		$data = ['a' => 0, 'b' => 1];
		$jsonResponse = new \Simple\Http\JsonResponse($data);
		$this->assertTrue($jsonResponse instanceof \Simple\HTTP\JsonResponse);
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
		$jsonResponse = new \Simple\Http\JsonResponse($data);
		ob_start();
		$jsonResponse->output();
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
		$jsonResponse = new \Simple\Http\JsonResponse($data);
		$jsonResponse->setHeader('Content-Type', 'application/octet-stream');
		try {
			$jsonResponse->output();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
