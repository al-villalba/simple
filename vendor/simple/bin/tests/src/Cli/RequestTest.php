<?php

namespace Simple\Cli; // needed to mock getopt()

use PHPUnit\Framework\TestCase;

/**
 * Mock getopt()
 * 
 * @return string
 */
function getopt($options, $longopts =[], &$optind = 0)
{
	global $argv;
	
	for($i = 1; $i < count($argv); $i++ ) {
		if( $argv[$i][0] != '-' ) {
			break;
		}
	}
	if( isset($argv[$i]) && $argv[$i][0] == '-' ) {
		$i++;
	}
	$optind = $i;
	
	return CliRequestTest::$MOCK_GETOPT;
}


/**
 * Test Simple\Cli\Request
 */
class CliRequestTest extends TestCase
{
	public static $MOCK_GETOPT;

	/**
	 * test __construct()
	 */
	public function test__construct()
	{
		$request = new Request();
		$this->assertTrue($request instanceof \Simple\Cli\Request);
		
		$this->assertTrue(($request->getOptions() === null || 
			is_array($request->getOptions())));
		$this->assertTrue(is_array($request->getArgs()));
	}

	/**
	 * test getParam()
	 */
	public function testGetParam()
	{
		global $argv;
		
		// simulate > cmd --get="a=0&b=1" -g "c=2" Homepage/index
		$bakArgv = $argv;
		$argv = [
			$argv[0],
			'--get="a=0&b=1"',
			'-g "c=2"',
			'Homepage/index'
		];
		self::$MOCK_GETOPT = [
			'get' => 'a=0&b=1',
			'g'   => 'c=2'
		];
		
		$request = new \Simple\Cli\Request();
		
		$argv = $bakArgv;
		
		$this->assertSame('Homepage/index', $request->getParam(0));
		$this->assertSame('0', $request->getParam('a'));
		$this->assertSame('1', $request->getParam('b'));
		$this->assertSame('2', $request->getParam('c'));
		
		// simulate > cmd --post="a=0&b=1" -p "c=2" Homepage/index
		$bakArgv = $argv;
		$argv = [
			$argv[0],
			'--post="a=0&b=1"',
			'-p "c=2"',
			'Homepage/index'
		];
		self::$MOCK_GETOPT = [
			'post' => 'a=0&b=1',
			'p'   => 'c=2'
		];
		
		$request = new \Simple\Cli\Request();
		
		$argv = $bakArgv;
		
		$this->assertSame('Homepage/index', $request->getParam(0));
		$this->assertSame('0', $request->getParam('a'));
		$this->assertSame('1', $request->getParam('b'));
		$this->assertSame('2', $request->getParam('c'));
	}

	/**
	 * test getParams()
	 */
	public function testGetParams()
	{
		global $argv;
		
		// simulate > cmd --post="a=0&b=1" -p "c=2" Homepage/index
		$bakArgv = $argv;
		$argv = [
			$argv[0],
			'--post="a=0&b=1"',
			'-p "c=2"',
			'Homepage/index'
		];
		self::$MOCK_GETOPT = [
			'post' => 'a=0&b=1',
			'p'   => 'c=2'
		];
		
		$request = new \Simple\Cli\Request();
		
		$argv = $bakArgv;
		
		$this->assertEquals(
			['Homepage/index', 'a' => '0', 'b' => '1', 'c' => '2'],
			$request->getParams());
	}

}
