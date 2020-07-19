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
	protected $_backup = [];

	protected function setUp()
	{
		$app = \Simple\Application::getInstance();
		
		// backup used globals
		$this->_backup['_SERVER'] = $_SERVER;
		$this->_backup['config'] = $app['config'];
		
		$config = $app['config'];
		array_walk_recursive($config, function(&$v, $k) {
			if( $k == 'namespace' ) {
				$v = 'Simple';
			}
		});
		$app['config'] = $config;
		
		parent::setUp();
	}

	protected function tearDown()
	{
		// restore globals
		$_SERVER = $this->_backup['_SERVER'];
		\Simple\Application::getInstance()['config'] = $this->_backup['config'];
		if( isset(\Simple\Application::getInstance()['sapi_name']) ) {
			unset(\Simple\Application::getInstance()['sapi_name']);
		}

		parent::tearDown();
	}

	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		// cgi request (GET)
		\Simple\Application::getInstance()['php_sapi'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');
		
		\Simple\Application::getInstance()['route'] = new \Simple\Route();
		$request = new \Simple\Http\Request();
		$this->assertTrue($request instanceof \Simple\HTTP\Request);
	}

	/**
	 * test getParam()
	 */
	public function testGetParam()
	{
		// cgi request (GET)
		\Simple\Application::getInstance()['php_sapi'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');
		$_GET = ['a' => 0, 'b' => 1, 'c' => [true, false]];

		\Simple\Application::getInstance()['route'] = new \Simple\Route();
		$request = new \Simple\Http\Request();

		$this->assertSame('0', $request->getParam('a'));
		$this->assertSame('1', $request->getParam('b'));
		$this->assertSame([true, false], $request->getParam('c'));
		
		// simulate > cmd --post="a=0&b=1" -p "c=2" Homepage/index
		unset(\Simple\Application::getInstance()['php_sapi']);
	}

	/**
	 * test getParams()
	 */
	public function testGetParams()
	{
		// cgi request (GET)
		\Simple\Application::getInstance()['php_sapi'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');
		$_GET = ['a' => 0, 'b' => 1, 'c' => [true, false]];
		
		\Simple\Application::getInstance()['route'] = new \Simple\Route();
		$request = new \Simple\Http\Request();
		
		$this->assertEquals(
			['a' => '0', 'b' => '1', 'c' => [true, null]],
			$request->getParams());

		unset(\Simple\Application::getInstance()['php_sapi']);
	}

}
