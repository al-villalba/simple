<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Application
 * (singleton)
 * @runTestsInSeparateProcesses
 */
class ApplicationTest extends TestCase
{
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

		// backup used globals
		$this->_globals['_SERVER'] = $_SERVER;
		$this->_globals['argv'] = $argv;

		if( !class_exists(\Simple\Cli\CliRequestTest::class) ) {
			include_once __DIR__ . '/Cli/RequestTest.php';
		}

		parent::setUp();
	}

	protected function tearDown()
	{
		global $argv;

		// restore globals
		foreach( $this->_globals as $var => $value ) {
			$$var = $value;
		}

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
		$app['dummy'] = function() {return 'dummy';};
		$this->assertSame('dummy', $app['dummy']);
		unset($app['dummy']);
		$this->assertFalse(isset($app['dummy']));
		$this->assertSame(null, $app['dummy']);
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
		$slug = \Simple\strSlugify($str);
		$this->assertSame('camel-case-test', $slug);
		
		$_str = \Simple\strCamelCase($slug);
		$this->assertSame('CamelCaseTest', $_str);
	}

	/**
	 * Test app['db']; \PDO::query + \PDOStatement::fetch
	 */
	public function testDbFetch()
	{
		$app = \Simple\Application::getInstance();
		
		if( empty($app['config']['database']) ) {
			$this->assertNull($app['db']);
			return;
		}
		$this->assertTrue($app['db'] instanceof \PDO);

		// simple query
		$q = "SHOW TABLES";
		$stmt = $app['db']->query($q);
		$results = $stmt->fetchAll(\PDO::FETCH_COLUMN);

		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		// prepared query with ? parameters
		$q = "SHOW TABLES LIKE ?";
		$stmt = $app['db']->prepare($q);
		$stmt->execute(['%']);
		$results = $stmt->fetchAll(\PDO::FETCH_COLUMN);

		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		// prepared query with named parameters
		$q = "SHOW TABLES LIKE :tablename";
		$stmt = $app['db']->prepare($q);
		$stmt->execute([':tablename' => '%']);
		$results = $stmt->fetchAll(\PDO::FETCH_COLUMN);

		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);
	}

	/**
	 * Test app['db']; \PDO::exec
	 */
	public function testDbExec()
	{
		$app = \Simple\Application::getInstance();
		
		$app['db']->exec("SET time_zone = '+00:00'");
		$this->assertSame('00000', $app['db']->errorCode());

		$stmt = $app['db']->query("SELECT @@global.time_zone, @@session.time_zone");
		$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertSame([['@@global.time_zone' => 'SYSTEM', '@@session.time_zone' => '+00:00']], $results);
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

		$object = $app->run();

		$this->assertTrue($object instanceof \Simple\Application);
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

		$object = $app->run();

		$this->assertTrue($object instanceof \Simple\Application);
	}

	/**
	 * Test run() with exception
	 */
	public function testRun_exception()
	{
		$app = \Simple\Application::getInstance();
		
		$stubController = 
			$this->createMock(\Simple\Controller\Homepage::class);
		$stubController->expects($this->once())
				->method('index')
				->willReturn(null);
		$app['controller'] = $stubController;

		// simulate cgi request
		$app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$e = null;
		try {
			$object = $app->run();
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
			$object = $app->run();
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
			$object = $app->run();
		} catch(\Error $e) {
		}
		$this->assertTrue($e instanceof \Error);
	}

}
