<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Http
 */
class HttpTest extends TestCase
{
	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		$http = new \Simple\Http('Test', "200");
		$this->assertEquals(200, $http->getCode());
		$this->assertEquals([], $http->getHeaders());
		$this->assertEquals('Test', $http->getBody());

		$http = new \Simple\Http('', 200);
		$this->assertEquals(200, $http->getCode());
		$this->assertEquals([], $http->getHeaders());
		$this->assertEquals('', $http->getBody());

		$http = new \Simple\Http('Test',
			"HTTP/1.1 200 OK\r\ncontent-type: text/plain\r\nAccess-Control-Allow-Methods: GET\r\nAccess-Control-Allow-Methods: POST\r\nAccess-Control-Allow-Methods: DELETE");
		$this->assertEquals('GET,POST,DELETE', $http->getHeader('Access-Control-Allow-Methods'));
		$this->assertEquals(
			['Content-Type' => 'text/plain', 'Access-Control-Allow-Methods' => 'GET,POST,DELETE'],
			$http->getHeaders());
	}

	/**
	 * test setHeader()
	 */
	public function testSetHeader()
	{
		$http = new \Simple\Http('Test', 200);
		$http->setHeader('Content-Type', 'text/plain');
		$this->assertEquals('text/plain', $http->getHeader('Content-Type'));
		
		$http->setHeader('Content-Type', 'text/html');
		$this->assertEquals('text/html', $http->getHeader('Content-Type'));
		
		$http->setHeader('Content-Type', 'text/html');
		$this->assertEquals('text/html', $http->getHeader('Content-Type'));
	}

	/**
	 * test unsetHeader()
	 */
	public function testUnsetHeader()
	{
		$http = new \Simple\Http('Test', 200);
		$http->setHeader('Content-Type', 'text/plain');
		$this->assertEquals('text/plain', $http->getHeader('Content-Type'));
		
		$http->unSetHeader('Content-Type');
		$this->assertSame(null, $http->getHeader('Content-Type'));
	}

	/**
	 * test getBody()
	 */
	public function testGetBody()
	{
		// content type www-form-urlencoded (as a request)
		$http = new \Simple\Http('a=0&b=1',
			"HTTP/1.1 200 OK\r\ncontent-type: application/x-www-form-urlencoded");
		$this->assertSame(['a' => '0', 'b' => '1'], $http->getBody());

		// content type www-form-urlencoded (as a response)
		$http = new \Simple\Http(['a' => '0', 'b' => '1'],
			"HTTP/1.1 200 OK\r\ncontent-type: application/x-www-form-urlencoded");
		$this->assertSame("HTTP/1.1 200 OK\r\nContent-Type: application/x-www-form-urlencoded\r\n\r\na=0&b=1", (string)$http);

		// content type json (as a request)
		$http = new \Simple\Http('{"a":0,"b":1}',
			"HTTP/1.1 200 OK\r\ncontent-type: application/json");
		$this->assertSame(['a' => 0, 'b' => 1], $http->getBody());

		// content type json (as a response)
		$http = new \Simple\Http(['a' => 0, 'b' => 1],
			"HTTP/1.1 200 OK\r\ncontent-type: application/json");
		$this->assertSame("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"a\":0,\"b\":1}", (string)$http);

		// content encoding gzip (as a request)
		$http = new \Simple\Http(gzencode('{"a":0,"b":1}'),
			"HTTP/1.1 200 OK\r\ncontent-encoding: gzip\r\ncontent-type: application/json");
		$this->assertSame(['a' => 0, 'b' => 1], $http->getBody());

		// content encoding gzip (as a response)
		$http = new \Simple\Http(['a' => 0, 'b' => 1],
			"HTTP/1.1 200 OK\r\ncontent-encoding: gzip\r\ncontent-type: application/json");
		$this->assertSame("HTTP/1.1 200 OK\r\nContent-Encoding: gzip\r\nContent-Type: application/json\r\n\r\n" . gzencode('{"a":0,"b":1}'), (string)$http);
	}

	/**
	 * test __toString()
	 */
	public function test__toString()
	{
		$http = new \Simple\Http('Test', 200);
		$http->setHeader(0, 'HTTP/1.1 200 OK');
		$http->setHeader('Keep-Alive', 'timeout=5');
		$http->setHeader('Keep-Alive', 'max=98', true);
		$this->assertEquals("HTTP/1.1 200 OK\r\nKeep-Alive: timeout=5,max=98\r\n\r\nTest", (string)$http);
	}

}
