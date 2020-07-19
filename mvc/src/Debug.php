<?php

/**
 * Debug provides static methods for debugging
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Debug
{
	const DEBUG_FILE = '/tmp/debug';
	
	/**
	 * An alternative to var_dump. It deploys $var in /tmp/debug.
	 * 
	 * @param mixed $var
	 * @param bool $screen
	 * @return void
	 */
	public static function fpc( $var = null, $screen = false )
	{
		if( !in_array(APP_ENV, ['local', 'dev', 'development']) &&
			!in_array(getenv('APP_ENV'), ['local', 'dev', 'development'])
		) {
			// do nothing
			return;
		}

		$output = '';
		if( empty($var) || is_bool($var) ) {
			$output = var_export($var, true) . "\n";
		} else {
			if( is_scalar($var) ) {
				$output = "$var\n";
			} else {
				$output = print_r($var, true);
			}
		}

		if( $screen ) {
			echo "<pre>\n" . $output . "</pre>\n";
		}

		file_put_contents(self::DEBUG_FILE, $output, FILE_APPEND);
	}

}
