<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Controller\Homepage
 * (avoid Error: Using $this in template)
 * @runTestsInSeparateProcesses
 */
class HomepageTest extends TestCase
{
	protected $_backup;

	protected function setUp()
	{
		global $argv;

		$app = \Simple\Application::getInstance();

		// backup used globals
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

		parent::tearDown();
	}

	/**
	 * Test instance of \Simple\RequestFactory
	 */
	public function testGetAttibutes()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$app = \Simple\Application::getInstance();

		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		// semi-run application
		list($controllerName, $actionName) = $app->initRequest();
		$controller = new $controllerName();
		$this->assertTrue($controller instanceof \Simple\Controller\Homepage);

		$this->assertSame('Simple', $controller->getNamespace());
		$this->assertSame('Homepage', $controller->getController());
		$this->assertSame('index', $controller->getAction());
		$this->assertSame([], $controller->getParams());
		$this->assertSame(null, $controller->getParam('void'));
		$this->assertSame(false, $controller->getParam('void', false));
	}

	/**
	 * Test Simple\Controller\_ControllerAbstract::renderFile()
	 */
	public function testRenderFile()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$app = \Simple\Application::getInstance();

		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		// semi-run application
		list($controllerName, $actionName) = $app->initRequest();
		$controller = new $controllerName();
		/** @var \Simple\Http\Response */
		$response = $controller->renderFile('homepage.phtml');

		$this->assertTrue($response instanceof \Simple\ResponseInterface);
		$this->assertTrue((bool)preg_match('/<html/', $response->getBody()));
		$this->assertTrue((bool)preg_match('/<head>/', $response->getBody()));
		$this->assertTrue((bool)preg_match('/<body/', $response->getBody()));
	}

	/**
	 * Test Simple\Controller\_ControllerAbstract::renderAction()
	 */
	public function testRenderAction()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$app = \Simple\Application::getInstance();

		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		// semi-run application
		list($controllerName, $actionName) = $app->initRequest();
		$controller = new $controllerName();
		/** @var \Simple\Http\Response */
		$response = $controller->renderAction([$controller, 'index']);

		$this->assertTrue($response instanceof \Simple\ResponseInterface);
		$this->assertTrue((bool)preg_match('/<html/', $response->getBody()));
		$this->assertTrue((bool)preg_match('/<head>/', $response->getBody()));
		$this->assertTrue((bool)preg_match('/<body/', $response->getBody()));
	}

	/**
	 * Test Simple\Controller\_ControllerAbstract::redirect()
	 */
	public function testRedirect()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$app = \Simple\Application::getInstance();

		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		// semi-run application
		list($controllerName, $actionName) = $app->initRequest();
		$controller = new $controllerName();
		/** @var \Simple\Http\Response */
		$response = $controller->redirect('/');

		$this->assertTrue($response instanceof \Simple\ResponseInterface);
		$this->assertSame('/', $response->getHeader('Location'));
	}

}
