<?php

use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
	/**
	 * Test Logger::log_file()
	 */
	public function testLogFile()
	{
		$app = \Simple::app();

		// manipulate config
		$config = $app->config;
		$configFile = $config['log']['means']['file'];
		$config['log']['means']['file'] = true;
		$app->config = $config;
		
		$this->assertEquals(PATH_ROOT . '/var/log/app.log', \Simple\Logger::FILE_PATH);
		$msg = 'Unit Test log_file!';
		\Simple\Logger::info($msg);
		$output = [];
		$status = null;
		exec('tail -1 ' . \Simple\Logger::FILE_PATH, $output, $status);
		$this->assertSame(0, $status);
		$this->assertTrue(is_array($output) && count($output) == 1);
		$this->assertSame($msg, substr($output[0], -1 * strlen($msg)));
		
		// restore config
		$config['log']['means']['file'] = $configFile;
		$app->config = $config;
	}

	/**
	 * Test Logger::log_stdout()
	 */
	public function testLogStdout()
	{
		$app = \Simple::app();

		// manipulate config
		$config = $app->config;
		$configStdout = $config['log']['means']['stdout'];
		$config['log']['means']['stdout'] = true;
		$app->config = $config;
		
		$msg = 'Unit Test log_stdout!';
		ob_start();
		\Simple\Logger::info($msg);
		$output = ob_get_clean();
		$this->assertSame($msg, substr(rtrim($output), -1 * strlen($msg)));
		
		// restore config
		$config['log']['means']['stdout'] = $configStdout;
		$app->config = $config;
	}

	/**
	 * Test Logger::log_stderr()
	 */
	public function testLogStderr()
	{
		$app = \Simple::app();

		// manipulate config
		$config = $app->config;
		$configStderr = $config['log']['means']['stderr'];
		$config['log']['means']['stderr'] = true;
		$app->config = $config;
		
		$msg = 'Unit Test log_stderr!';
		ob_start();
		\Simple\Logger::info($msg);
		$output = ob_get_clean();
		// ob_start does not capture stderr!
		// This is not testable! The log written to stderr will be in the console
//		$this->assertSame($msg, substr(rtrim($output), -1 * strlen($msg)));
		$this->assertSame('', substr(rtrim($output), -1 * strlen($msg)));
		
		// restore config
		$config['log']['means']['stderr'] = $configStderr;
		$app->config = $config;
	}

	/**
	 * Test Logger::log_syslog()
	 */
	public function testLogSyslog()
	{
		$app = \Simple::app();

		// manipulate config
		$config = $app->config;
		$configSyslog = $config['log']['means']['syslog'];
		$config['log']['means']['syslog'] = true;
		$app->config = $config;
		
		$msg = 'Unit Test log_syslog!';
		\Simple\Logger::info($msg);
		$output = [];
		$status = null;
		exec('tail -1 /var/log/syslog', $output, $status);
		$this->assertSame(0, $status);
		$this->assertTrue(is_array($output) && count($output) == 1);
		$this->assertSame($msg, substr($output[0], -1 * strlen($msg)));
		
		// restore config
		$config['log']['means']['syslog'] = $configSyslog;
		$app->config = $config;
	}

}
