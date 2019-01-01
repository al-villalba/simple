<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Http\Response
 */
class HttpResponseTest extends TestCase
{
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

		// content encoding gzip (as a response)
		$data = ['a' => 0, 'b' => 1];
		$response = new \Simple\Http\Response(
			$data,
			[
				'content-encoding' => 'gzip',
				'content-type'     => 'application/json'
			]
		);
		ob_start();
		$response->output();
		$output = ob_get_flush();
		$this->assertSame(gzencode(json_encode($data)), $output);
	}

	/**
	 * test output() with exception
	 */
	public function testOutput_exception()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		// content encoding gzip (as a response)
		$data = ['a' => 0, 'b' => 1];
		$response = new \Simple\Http\Response(
			$data,
			[
				'content-encoding' => 'gzip',
				'content-type'     => 'application/json'
			]
		);
		$e = null;
		try {
			if( !headers_sent() ) {
				// force send headers
				header("Content-Type: text/html");
				echo '<h1>Test</h1>';
			}
			$response->output();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
