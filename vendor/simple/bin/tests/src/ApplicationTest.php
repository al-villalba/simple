<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Application
 * @runTestsInSeparateProcesses
 */
class ApplicationTest extends TestCase
{
	/**
	 * Object under test (singleton)
	 * @var Simple\Application
	 */
	protected $_app = null;

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

		$this->_app = \Simple\Application::getInstance();

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

		$this->_app = null;

		parent::tearDown();
	}

	/**
	 * Test instance of \Simple\Application
	 */
	public function testInstanceOf()
	{
		$this->assertTrue($this->_app instanceof \Simple\Application);
		$this->assertTrue(is_array($this->_app['config']));
		$this->_app['dummy'] = function() {return 'dummy';};
		$this->assertSame('dummy', $this->_app['dummy']);
		unset($this->_app['dummy']);
		$this->assertFalse(isset($this->_app['dummy']));
		$this->assertSame(null, $this->_app['dummy']);
	}

	/**
	 * Test singleton features
	 */
	public function testSingleton()
	{
		$e = null;
		try {
			$dummy = clone $this->_app;
		} catch(\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);

		$e = null;
		try {
			$dummy = serialize($this->_app);
			$app = unserialize($dummy);
		} catch(\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * Test app['db']; \PDO::query + \PDOStatement::fetch
	 */
	public function testDbFetch()
	{
		if( empty($this->_app['config']['database']) ) {
			$this->assertNull($this->_app['db']);
			return;
		}
		$this->assertTrue($this->_app['db'] instanceof \PDO);

		// simple query
		$q = "SHOW TABLES";
		$stmt = $this->_app['db']->query($q);
		$results = $stmt->fetchAll(\PDO::FETCH_COLUMN);

		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		// prepared query with ? parameters
		$q = "SHOW TABLES LIKE ?";
		$stmt = $this->_app['db']->prepare($q);
		$stmt->execute(['%']);
		$results = $stmt->fetchAll(\PDO::FETCH_COLUMN);

		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		// prepared query with named parameters
		$q = "SHOW TABLES LIKE :tablename";
		$stmt = $this->_app['db']->prepare($q);
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
		$this->_app['db']->exec("SET time_zone = '+00:00'");
		$this->assertSame('00000', $this->_app['db']->errorCode());

		$stmt = $this->_app['db']->query("SELECT @@global.time_zone, @@session.time_zone");
		$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertSame([['@@global.time_zone' => 'SYSTEM', '@@session.time_zone' => '+00:00']], $results);
	}

	/**
	 * Test cli: bin/cli/appc.php --get="a=0&b=1" dummy
	 */
	public function testRunCli()
	{
		global $argv;

		// supress output to stdout
		$this->setOutputCallback(function() {});

		// prepare args
		$this->_app['sapi_name'] = 'cli';
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

		$object = $this->_app->run();

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

		// simulate cgi request
		$this->_app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$object = $this->_app->run();

		$this->assertTrue($object instanceof \Simple\Application);
	}

	/**
	 * Test run() with exception
	 */
	public function testRun_exception()
	{
		$stubController = 
			$this->createMock(\Simple\Controller\Homepage::class);
		$stubController->expects($this->once())
				->method('index')
				->willReturn(null);
		$this->_app['controller'] = $stubController;

		// simulate cgi request
		$this->_app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/');

		$e = null;
		try {
			$object = $this->_app->run();
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

		// prepare args
		$this->_app['sapi_name'] = 'cli';
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
			$object = $this->_app->run();
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
		// simulate cgi request
		$this->_app['sapi_name'] = 'cgi';
		$server = new \Jelix\FakeServerConf\ApacheMod('/var/www/simple/bin/www/');
		$server->setHttpRequest('http://simple.poc.local/UndefinedController/undefinedAction');

		try {
			$object = $this->_app->run();
		} catch(\Error $e) {
		}
		$this->assertTrue($e instanceof \Error);
	}

}
