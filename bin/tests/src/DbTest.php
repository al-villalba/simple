<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\Db
 */
class DbTest extends TestCase
{
	protected $_backup;

	protected function setUp()
	{
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
	 * test __construct()
	 */
	public function test__construct()
	{
		$app = \Simple\Application::getInstance();
		$this->assertFalse(empty($app['config']['database']));

		$db = new \Simple\Db();
		$this->assertTrue($db instanceof \PDO);
	}

	/**
	 * test __construct() with exception
	 */
	public function test__construct_exception()
	{
		$app = \Simple\Application::getInstance();
		$config = $app['config'];
		unset($config['database']);
		$app['config'] = $config;
		$e = null;
		try {
			$db = new \Simple\Db();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);

		$config = $this->_backup['config'];
		$config['database']['password'] = 'non-exiting-password';
		$app['config'] = $config;
		$e = null;
		try {
			$db = new \Simple\Db();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * Test db::_fetchAll, db::_fetchScalar
	 */
	public function testFetch()
	{
		$db = new \Simple\Db();

		// simple query
		$results = $db->_fetchAll("SHOW TABLES");
		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		// prepared query with ? parameters
		$results = $db->_fetchAll("SHOW TABLES LIKE ?",
			['%'], \PDO::FETCH_COLUMN);
		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		// prepared query with named parameters
		$tables = $db->_fetchAll("SHOW TABLES LIKE :tablename",
			[':tablename' => '%'], \PDO::FETCH_COLUMN);
		$this->assertTrue(is_array($tables));
		$this->assertTrue(count($tables) > 0);

		// fetch scalar
		$scalar = $db->_fetchScalar("SHOW TABLES LIKE :tablename",
			[':tablename' => $tables[0]], \PDO::FETCH_COLUMN);
		$this->assertTrue(is_string($scalar));
		$this->assertSame($tables[0], $scalar);
	}

	/**
	 * Test db::_fetchAll, db::_fetchScalar with Exception
	 */
	public function testFetch_exception()
	{
		$db = new \Simple\Db();

		$e = null;
		try {
			$results = $db->_fetchAll("SHOW TABLE");
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);

		$e = null;
		try {
			$results = $db->_fetchScalar("SHOW TABLE");
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * Test db::_execute
	 */
	public function testExecute()
	{
		$db = new \Simple\Db();

		$db->_execute("SET time_zone = '+01:00'");
		$this->assertSame('00000', $db->errorCode());
		$results = $db->_fetchAll("SELECT @@global.time_zone, @@session.time_zone");
		$this->assertSame([['@@global.time_zone' => 'SYSTEM', '@@session.time_zone' => '+01:00']], $results);

		$db->_execute("SET time_zone = ?", ['+00:00']);
		$this->assertSame('00000', $db->errorCode());
		$results = $db->_fetchAll("SELECT @@global.time_zone, @@session.time_zone");
		$this->assertSame([['@@global.time_zone' => 'SYSTEM', '@@session.time_zone' => '+00:00']], $results);
	}

	/**
	 * Test db::_execute with exception
	 */
	public function testExecute_exception()
	{
		$db = new \Simple\Db();

		$e = null;
		try {
			$db->_execute("SETT time_zone = '+01:00'");
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * Test db::_bindParams
	 */
	public function testBindParams()
	{
		$db = new \Simple\Db();

		// simple query
		$results = $db->_fetchAll("SELECT * FROM user WHERE user_id = ?", [1]);
		$this->assertTrue(is_array($results));
		$this->assertTrue(count($results) > 0);

		$results = $db->_fetchAll("SELECT * FROM user WHERE user_id = ?", [null]);
		$this->assertTrue(is_array($results));
		$this->assertTrue(empty($results));
	}

}
