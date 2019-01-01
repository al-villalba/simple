<?php

use PHPUnit\Framework\TestCase;

use Simple\ApplicationTest;

/**
 * Test Simple\Http\Request
 */
class HttpRequestTest extends TestCase
{
	/**
	 * Used globals, to backup and restore
	 * @var array
	 */
	protected $_globals = [];

	protected function setUp()
	{
		// backup used globals
		$this->_globals['_SERVER'] = $_SERVER;
		
		parent::setUp();
	}

	protected function tearDown()
	{
		// restore globals
		foreach( $this->_globals as $var => $value ) {
			$$var = $value;
		}

		if( isset($this->_app['sapi_name']) ) {
			unset($this->_app['sapi_name']);
		}

		parent::tearDown();
	}

	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		$request = new \Simple\Http\Request();
		$this->assertTrue($request instanceof \Simple\HTTP\Request);
	}

	/**
	 * test getParam()
	 */
	public function testGetParam()
	{
		// simulate cgi request (GET)
		\Simple\Application::getInstance()['php_sapi'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');
		$_GET = ['a' => 0, 'b' => 1];

		$request = new \Simple\Http\Request();

		$this->assertSame('0', $request->getParam('a'));
		$this->assertSame('1', $request->getParam('b'));
		
		// simulate > cmd --post="a=0&b=1" -p "c=2" Homepage/index
		unset(\Simple\Application::getInstance()['php_sapi']);
	}

	/**
	 * test getParams()
	 */
	public function testGetParams()
	{
		// simulate cgi request (GET)
		\Simple\Application::getInstance()['php_sapi'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');
		$_GET = ['a' => 0, 'b' => 1];
		
		$request = new \Simple\Http\Request();
		
		$this->assertEquals(
			['a' => '0', 'b' => '1'],
			$request->getParams());

		unset(\Simple\Application::getInstance()['php_sapi']);
	}

}
