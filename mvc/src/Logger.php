<?php

namespace Simple;

/**
 * Log provides functionality to log messages.
 * Use the static methods!
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Logger
{
	/**
	 * Log levels
	 */
	const LEVEL = [
		'DEBUG'   => 1,
		'INFO'    => 2,
		'WARNING' => 3,
		'ERROR'   => 4
	];

	/**
	 * Default log file
	 * @see config/log.json:means:file
	 */
	const FILE_PATH = PATH_VAR . '/log/app.log';

	/**
	 * Self object only for internal use.
	 * Use static methods! (public functions were intended to be protected, but
	 * they are called from the logger in static methods)
	 */
	protected function __construct() {
	}

	/**
	 * Log any level
	 * 
	 * @param string $msg
	 * @param int $level
	 */
	public static function log($msg, $level = null)
	{
		$app = \Simple::app();

		$levelFilter = self::LEVEL[strtoupper($app->config['log']['level'])];
		if( $level < $levelFilter ) {
			return;
		}

		$logger = new self();
		foreach( $app->config['log']['means'] as $means => $value ) {
			if( $value && is_callable([$logger, "log_$means"]) ) {
				call_user_func([$logger, "log_$means"],
					$logger->normalize($msg, $level));
			}
		}
	}

	/**
	 * Log debug messages
	 * 
	 * @param type $msg
	 */
	public static function debug($msg)
	{
		return self::log($msg, self::LEVEL['DEBUG']);
	}

	/**
	 * Log info messages
	 * 
	 * @param type $msg
	 */
	public static function info($msg)
	{
		return self::log($msg, self::LEVEL['INFO']);
	}

	/**
	 * Log warning messages
	 * 
	 * @param type $msg
	 */
	public static function warning($msg)
	{
		return self::log($msg, self::LEVEL['WARNING']);
	}

	/**
	 * Log error messages
	 * 
	 * @param type $msg
	 */
	public static function error($msg)
	{
		return self::log($msg, self::LEVEL['ERROR']);
	}

	/**
	 * Prepare a variable to be written in a log message, i.e.
	 * A string representation of $var
	 * 
	 * @param mixed $var
	 * @return string
	 */
	public static function var($var)
	{
		$output = '';
		if( empty($var) || is_bool($var) ) {
			$output = var_export($var, true);
		} else {
			if( is_scalar($var) ) {
				$output = "$var";
			} else {
				$output = print_r($var, true);
			}
		}

		return $output;
	}

	/**
	 * Normalize message:
	 * It defines a line with the format "Timestamp LEVEL : Message"
	 * 
	 * @param string $msg
	 * @return string
	 */
	public function normalize($msg, $level)
	{
		$size = max(array_map(
			function($str) {return strlen($str);},
			array_keys(self::LEVEL))
		);
		$line = date('Y-m-d H:i:s ')
			. sprintf("%-{$size}s : ", array_flip(self::LEVEL)[$level])
			. self::var($msg);

		return "$line\n";
	}

	/**
	 * Log $msg in file. $msg must be normalized previously
	 * 
	 * @param string $msg
	 * @param int $level
	 */
	public function log_file($msg)
	{
		$app = \Simple::app();

		// prepare path
		$path = $app->config['log']['means']['file'];
		if( is_bool($path) ) {
			$path = self::FILE_PATH;
		}
		if( !file_exists($path) ) {
			if( !file_exists(dirname($path)) ) {
				mkdir(dirname($path), 0755);
			}
			file_put_contents($path, "");
		}

		// write log
		file_put_contents($path, $msg, FILE_APPEND);
	}

	public function log_stdout($msg)
	{
		file_put_contents('php://output', $msg); // synonym of `echo $msg`
	}

	public function log_stderr($msg)
	{
		file_put_contents('php://stderr', $msg);
	}

	public function log_syslog($msg)
	{
		if( preg_match('/ ERROR /', $msg) ) {
			syslog(LOG_ERR, $msg);
		} elseif( preg_match('/ WARNING /', $msg) ) {
			syslog(LOG_WARNING, $msg);
		} elseif( preg_match('/ INFO /', $msg) ) {
			syslog(LOG_INFO, $msg);
		} elseif( preg_match('/ DEBUG /', $msg) ) {
			syslog(LOG_DEBUG, $msg);
		}
	}

	public function log_db($msg)
	{
		throw new \Exception('Not implemented');
	}

}
