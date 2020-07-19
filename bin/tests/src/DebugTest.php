<?php

use PHPUnit\Framework\TestCase;

class DebugTest extends TestCase
{
	protected function setUp()
	{
		if( file_exists(Debug::DEBUG_FILE) ) {
			unlink(Debug::DEBUG_FILE);
		}
	}

	protected function tearDown()
	{
		if( file_exists(Debug::DEBUG_FILE) ) {
			unlink(Debug::DEBUG_FILE);
		}
	}

	public function boolProvider()
	{
		return [
			[null],
			[false],
			[true]
		];
	}

	/**
	 * Test Debug::fpc(_bool)
	 * 
	 * @dataProvider boolProvider
	 */
	public function testBoolVar($bool)
	{
		Debug::fpc($bool);
		$output = trim(file_get_contents(Debug::DEBUG_FILE));
		eval('$var = ' . $output . ';');
		$this->assertSame($bool, $var);
	}

	public function scalarProvider()
	{
		return [
			['dummy text'],
			[1],
			[1.2]
		];
	}

	/**
	 * Test Debug::fpc(_scalar)
	 * 
	 * @dataProvider scalarProvider
	 */
	public function testScalarVar($scalar)
	{
		Debug::fpc($scalar);
		$output = trim(file_get_contents(Debug::DEBUG_FILE));
		eval('$var = "' . $output . '";');
		$this->assertEquals($scalar, $var);
	}

	public function compoundProvider()
	{
		$obj = new stdClass();
		$obj->a = 1;
		$obj->b = 2;

		return [
			[['a' => 1, 'b' => 2]],
			[$obj]
		];
	}

	/**
	 * Test Debug::fpc(_scalar)
	 * 
	 * @dataProvider compoundProvider
	 */
	public function testCompundVar($compound)
	{
		Debug::fpc($compound);
		$output = trim(file_get_contents(Debug::DEBUG_FILE));
		$this->assertSame(trim(print_r($compound, true)), $output);
	}

	/**
	 * Test Debug::fpc(mixed, true)
	 */
	public function testScreenTrue()
	{
		// supress output to stdout
		$this->setOutputCallback(function() {});

		$dummy = 'dummy';
		ob_start();
		Debug::fpc($dummy, true);
		$output = ob_get_flush();
		$this->assertSame("<pre>\n$dummy\n</pre>\n", $output);
	}

	/**
	 * Test Debug::fpc() in production
	 */
	public function testEnvProduction()
	{
		putenv('APP_ENV=production');

		Debug::fpc('dummy');
		$this->assertFalse(file_exists(Debug::DEBUG_FILE));
	}

}
