<?php

use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
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

		parent::tearDown();
	}

	public function testGetHomepage()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$route = new \Simple\Route(new \Simple\Http\Request());
		$r = $route->get();
		$this->assertSame('/', $r['_match']);
	}

	public function testGetController()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ctrl/?p1=v1');

		$route = new \Simple\Route(new \Simple\Http\Request());
		$r = $route->get();
		$this->assertSame('/{controller}', $r['_match']);
		$this->assertSame(['p1' => 'v1'], $r['query']);
	}

	public function testGetControllerAction()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ctrl/act/?p1=v1');

		$route = new \Simple\Route(new \Simple\Http\Request());
		$r = $route->get();
		$this->assertSame('/{controller}/{action}', $r['_match']);
		$this->assertSame(['p1' => 'v1'], $r['query']);
	}

	public function testGetControllerActionParams()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ctrl/act/p1/v1/?p2=v2');

		$route = new \Simple\Route(new \Simple\Http\Request());
		$r = $route->get();
		$this->assertSame('/{controller}/{action}/*', $r['_match']);
		$this->assertSame(['p2' => 'v2', 'p1' => 'v1'], $r['query']);
	}

	public function testGetNamespaceControllerAction()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ns/ctrl/act/?p1=v1');

		$route = new \Simple\Route(new \Simple\Http\Request());
		$r = $route->get();
		$this->assertSame('/{namespace}/{controller}/{action}', $r['_match']);
		$this->assertSame(['p1' => 'v1'], $r['query']);
	}

	public function testGetNamespaceControllerActionParams()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ns/ctrl/act/p1/v1/?p2=v2');

		$route = new \Simple\Route(new \Simple\Http\Request());
		$r = $route->get();
		$this->assertSame('/{namespace}/{controller}/{action}/*', $r['_match']);
		$this->assertSame(['p2' => 'v2', 'p1' => 'v1'], $r['query']);
	}

	public function testGet_exception()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$route = new \Simple\Route(null);
		$e = null;
		try {
			$r = $route->get();
		} catch( \Exception $e ) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
