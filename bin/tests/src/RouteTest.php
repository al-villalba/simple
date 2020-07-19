<?php

use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
	protected $_backup;
	
	protected function setUp()
	{
		global $argv;

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
		\Simple\Application::getInstance()['config'] = $this->_backup['config'];
		$_SERVER = $this->_backup['_SERVER'];

		parent::tearDown();
	}

	public function testGetHomepage()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/',
			'namespace'  => 'Simple',
			'controller' => 'Homepage',
			'action'     => 'index',
			'query'      => []
		], $r);
	}

	public function testGetController()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ctrl/?p1=v1');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{controller}',
			'namespace'  => 'Simple',
			'controller' => 'Ctrl',
			'action'     => 'index',
			'query'      => ['p1' => 'v1']
		], $r);
	}

	public function testGetControllerAction()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ctrl/act/?p1=v1');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{controller}/{action}',
			'namespace'  => 'Simple',
			'controller' => 'Ctrl',
			'action'     => 'act',
			'query'      => ['p1' => 'v1']
		], $r);
	}

	public function testGetControllerActionParams()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ctrl/act/p1/v1/?p2=v2');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{controller}/{action}/*',
			'namespace'  => 'Simple',
			'controller' => 'Ctrl',
			'action'     => 'act',
			'query'      => ['p1' => 'v1', 'p2' => 'v2']
		], $r);
	}

	public function testGetNamespaceControllerAction()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ns/ctrl/act/?p1=v1');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{namespace}/{controller}/{action}',
			'namespace'  => 'Ns',
			'controller' => 'Ctrl',
			'action'     => 'act',
			'query'      => ['p1' => 'v1']
		], $r);
	}

	public function testGetNamespaceControllerActionParams()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ns/ctrl/act/p1/v1/?p2=v2');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{namespace}/{controller}/{action}/*',
			'namespace'  => 'Ns',
			'controller' => 'Ctrl',
			'action'     => 'act',
			'query'      => ['p1' => 'v1', 'p2' => 'v2']
		], $r);
	}

	public function testGet_exception()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$app = \Simple\Application::getInstance();
		$configRoutingBak = $app['config']['routing'];
		$app['config'] = array_merge(
			$app['config'],
			['routing' => null]
		);
		
		$route = new \Simple\Route();
		$e = null;
		try {
			$r = $route->get();
		} catch( \Exception $e ) {
		}
		$this->assertTrue($e instanceof \Exception);
		$app['config'] = array_merge(
			$app['config'],
			['routing' => $configRoutingBak]
		);
	}

	public function testWrongParams()
	{
		// simulate cgi call
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/ns/ctrl/act/p1/v1/p2?p3=v3');

		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{controller}/{action}/*',
			'namespace'  => 'Simple',
			'controller' => 'Ns',
			'action'     => 'ctrl',
			'query'      => ['act' => 'p1', 'v1' => 'p2', 'p3' => 'v3']
		], $r);
		
		$server->setHttpRequest('http://simple.poc.local/ns/ctrl/act/p1/?p2=v2');
		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{controller}/{action}/*',
			'namespace'  => 'Simple',
			'controller' => 'Ns',
			'action'     => 'ctrl',
			'query'      => ['act' => 'p1', 'p2' => 'v2']
		], $r);
		
		$server->setHttpRequest('http://simple.poc.local/ctrl/act/p1/?p2=v2');
		$route = new \Simple\Route();
		$r = $route->get();
		$this->assertSame([
			'_match' => '/{namespace}/{controller}/{action}',
			'namespace'  => 'Ctrl',
			'controller' => 'Act',
			'action'     => 'p1',
			'query'      => ['p2' => 'v2']
		], $r);
	}

}
