<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\ResponseFactory
 */
class ResponseFactoryTest extends TestCase
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
	 * Test instance of \Simple\ResponseFactory
	 */
	public function testCreate()
	{
		\Simple\Application::getInstance()['sapi_name'] = 'cli';
		$response = \Simple\ResponseFactory::create('Test');
		$this->assertTrue($response instanceof \Simple\Cli\Response);
		// alias
		$response = \Simple\ResponseFactory::factory('Test');
		$this->assertTrue($response instanceof \Simple\Cli\Response);

		\Simple\Application::getInstance()['sapi_name'] = 'cgi';
		$response = \Simple\ResponseFactory::create('Test');
		$this->assertTrue($response instanceof \Simple\Http\Response);
		// alias
		$response = \Simple\ResponseFactory::factory('Test');
		$this->assertTrue($response instanceof \Simple\Http\Response);
	}

	public function test__construct()
	{
		try {
			$response = new \Simple\ResponseFactory();
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
