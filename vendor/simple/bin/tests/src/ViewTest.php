<?php

use PHPUnit\Framework\TestCase;

/**
 * Test Simple\View
 * @runTestsInSeparateProcesses
 */
class ViewTest extends TestCase
{
	/**
	 * test renderFile()
	 */
	public function testRenderFile()
	{
		$view = new Simple\View(null, \Simple\Controller\Homepage::class);
		$output = $view->renderFile('homepage.phtml');
		$this->assertTrue((bool)preg_match('/<html/', $output));
		$this->assertTrue((bool)preg_match('/<head>/', $output));
		$this->assertTrue((bool)preg_match('/<body/', $output));
	}

	/**
	 * test renderFile() with exception
	 */
	public function testRenderFile_exception()
	{
		$view = new Simple\View(null, \Simple\Controller\Homepage::class);
		$e = null;
		try {
			$output = $view->renderFile('undefinedTemplate.phtml');
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

	/**
	 * test block methods with exceptions
	 */
	public function testBlock_exception()
	{
		$view = new Simple\View(null, \Simple\Controller\Homepage::class);
		$view->startBlock();
		echo 'dummy';
		$view->endBlock('dummy');
		ob_start();
		$view->block('dummy');
		$dummyBlock = ob_get_clean();
		$this->assertSame('dummy', $dummyBlock);
		try {
			$view->startBlock();
			echo 'dummy';
			$view->endBlock('dummy');
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);

		try {
			$view->block('undefinedBlock');
		} catch (\Exception $e) {
		}
		$this->assertTrue($e instanceof \Exception);
	}

}
