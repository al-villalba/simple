<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\RequestFactory
 */
class RequestFactoryTest extends TestCase
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
		
		if( isset(\Simple\Application::getInstance()['sapi_name']) ) {
			unset(\Simple\Application::getInstance()['sapi_name']);
		}

		parent::tearDown();
	}

	/**
	 * Test instance of \Simple\RequestFactory
	 */
	public function testCreate()
	{
		\Simple\Application::getInstance()['sapi_name'] = 'cli';
		$request = \Simple\RequestFactory::create('Test');
		$this->assertTrue($request instanceof \Simple\Cli\Request);
		// alias
		$request = \Simple\RequestFactory::factory('Test');
		$this->assertTrue($request instanceof \Simple\Cli\Request);

		\Simple\Application::getInstance()['sapi_name'] = 'cgi';
		$request = \Simple\RequestFactory::create('Test');
		$this->assertTrue($request instanceof \Simple\Http\Request);
		// alias
		$request = \Simple\RequestFactory::factory('Test');
		$this->assertTrue($request instanceof \Simple\Http\Request);
	}

	public function test__construct()
	{
		try {
			$response = new \Simple\RequestFactory();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
