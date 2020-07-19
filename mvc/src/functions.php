<?php

//
// Global helper functions
//

function fpc( $var = null, $screen = false )
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

	file_put_contents('/tmp/debug', $output, FILE_APPEND);
}

/**
 * Generate slug from $string
 * 
 * @see https://github.com/phalcon/incubator/blob/master/Library/Phalcon/Utils/Slug.php
 * 
 * @param string $string
 * @param string $delimiter
 * @return string
 * @throws Exception
 */
function strSlugify($string, $delimiter = '-')
{
	// backup and set the new locale to UTF-8
	$oldLocale = setlocale(LC_ALL, '0');
	setlocale(LC_ALL, 'en_US.UTF-8');
	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
	$clean = preg_replace('/[^a-zA-Z0-9\/_|+ -]/', '', $clean);
	// camelCase to dash
	$clean = preg_replace('/(?<=\\w)(?=[A-Z])/', $delimiter.'$1', $clean);
	$clean = strtolower($clean);
	$clean = preg_replace('/[\/_|+ -]+/', $delimiter, $clean);
	$clean = trim($clean, $delimiter);
	
	// restore locale
	setlocale(LC_ALL, $oldLocale);
	
	return $clean;
}

/**
 * Generate camel case from slug
 * 
 * @param string $string
 * @return string
 */
function strCamelCase($string)
{
	$string = str_replace('-', ' ', $string);
	$string = ucwords($string);
	$string = str_replace(' ', '', $string);
	
	return $string;
}
