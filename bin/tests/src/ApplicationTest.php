<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Application
 * (singleton,
 *  avoid Error: Using $this in template, and
 *  http headers already sent in PHPUnit/Util/Printer.php
 * @runTestsInSeparateProcesses
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class ApplicationTest extends TestCase
{
	protected $_backup;
	
	/**
	 * @var array
	 */
	protected $_env = [
		'APP_ENV' => 'local',
		'DB_HOST' => 'localhost',
		'DB_NAME' => 'casino',
		'DB_USER' => 'casino',
		'DB_PASS' => 'casino'
	];

	protected function setUp()
	{
		global $argv;

		$app = \Simple\Application::getInstance();
		
		// backup used globals
		$this->_backup['_SERVER'] = $_SERVER;
		$this->_backup['argv'] = $argv;
		$this->_backup['config'] = $app['config'];
		
		$config = $app['config'];
		array_walk_recursive($config, function(&$v, $k) {
			if( $k == 'namespace' ) {
				$v = 'Simple';
			}
		});
		$app['config'] = $config;

		if( !class_exists(\Simple\Cli\CliRequestTest::class) ) {
			include_once __DIR__ . '/Cli/RequestTest.php';
		}

		parent::setUp();
	}

	protected function tearDown()
	{
		global $argv;

		// restore globals
		$_SERVER = $this->_backup['_SERVER'];
		$argv    = $this->_backup['argv'];
		\Simple\Application::getInstance()['config'] = $this->_backup['config'];

		parent::tearDown();
	}

	/**
	 * Test instance of \Simple\Application
	 */
	public function testInstanceOf()
	{
		$app = \Simple\Application::getInstance();
		
		$this->assertTrue($app instanceof \Simple\Application);
		$this->assertTrue(is_array($app['config']));

		// array acess
		$app['dummy'] = function() {return 'dummy';};
		$this->assertSame('dummy', $app['dummy']);
		if( isset($app['dummy']) ) {
			unset($app['dummy']);
		}

		// object access
		$app->dummy = function() {return 'dummy';};
		$this->assertSame('dummy', $app->dummy);
		if( isset($app->dummy) ) {
			unset($app->dummy);
		}
		
		$this->assertFalse(isset($app->dummy));
		$this->assertSame(null, $app->dummy);
		
		$this->assertTrue($app->db instanceof \Simple\Db);

		$this->assertTrue($app->requestFactory instanceof \Simple\RequestInterface);
	}

	/**
	 * Test singleton features
	 */
	public function testSingleton()
	{
		$app = \Simple\Application::getInstance();
		
		$e = null;
		try {
			$dummy = clone $app;
		} catch(\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);

		$e = null;
		try {
			$dummy = serialize($app);
			$_app = unserialize($dummy);
		} catch(\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * test global functions in bootstrap.php
	 */
	public function testGlobals()
	{
		$str = 'camelCaseTest';
		$slug = \strSlugify($str);
		$this->assertSame('camel-case-test', $slug);
		
		$_str = \strCamelCase($slug);
		$this->assertSame('CamelCaseTest', $_str);
	}

	/**
	 * Test cli: bin/cli/appc.php --get="a=0&b=1" dummy
	 */
	public function testRunCli()
	{
		global $argv;

		$app = \Simple\Application::getInstance();
		
		// supress output to stdout
		$this->setOutputCallback(function() {});

		// prepare args
		$app['sapi_name'] = 'cli';
		foreach( $this->_env as $var => $value ) {
			putenv("$var=$value");
		}
		$argv = [
			PATH_ROOT . '/bin/cli/appc.php',
			'--get="a=0&b=1"',
			"dummy"
		];
		\Simple\Cli\CliRequestTest::$MOCK_GETOPT = [
			'get' => 'a=0&b=1'
		];

		$exitStatus = $app->run();

		$this->assertSame(0, $exitStatus);
	}

	/**
	 * Test cgi: http://simple.poc.local/
	 * 
	 * Avoid "Http headers already sent in PHPUnit/Util/Printer.php"
	 * runInSeparateProcess
	 */
	public function testRunCgi()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$app = \Simple\Application::getInstance();
		
		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$exitStatus = $app->run();

		$this->assertSame(0, $exitStatus);
	}

	/**
	 * Test run() with exception
	 */
	public function testRun_exception()
	{
		$app = \Simple\Application::getInstance();
		
		$mockController = 
			$this->createMock(\Simple\Controller\Homepage::class);
		$mockController->expects($this->once())
				->method('index')
				->willReturn(null);
		$app['controller'] = $mockController;

		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$e = null;
		try {
			$exitStatus = $app->run();
		} catch(\Exception $e) {
		}

		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * Test cli: bin/cli/appc.php --get="a=0&b=1" UndefinedController/undefinedAction
	 */
	public function testRunCli_exception404()
	{
		global $argv;

		$app = \Simple\Application::getInstance();
		
		// prepare args
		$app['sapi_name'] = 'cli';
		foreach( $this->_env as $var => $value ) {
			putenv("$var=$value");
		}
		$argv = [
			PATH_ROOT . '/bin/cli/appc.php',
			'--get="a=0&b=1"',
			"UndefinedController/undefinedAction"
		];
		\Simple\Cli\CliRequestTest::$MOCK_GETOPT = [
			'get' => 'a=0&b=1'
		];

		$e = null;
		try {
			$exitStatus = $app->run();
		} catch(\Error $e) {
		}

		$this->assertTrue($e instanceof \Error);
	}

	/**
	 * Test cgi: http://simple.poc.local/UndefinedController/undefinedAction
	 * 
	 * Avoid "Http headers already sent in PHPUnit/Util/Printer.php"
	 * runInSeparateProcess
	 */
	public function testRunCgi_exception404()
	{
		$app = \Simple\Application::getInstance();
		
		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/UndefinedController/undefinedAction');

		try {
			$exitStatus = $app->run();
		} catch(\Error $e) {
		}
		$this->assertTrue($e instanceof \Error);
	}

}
