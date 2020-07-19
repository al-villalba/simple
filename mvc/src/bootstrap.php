<?php

namespace Simple;

/**
 * Init the php essentials to ease the initializtion of the app
 * Run within a local function
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
call_user_func(function() {
	
	// define essential paths. Further paths are defined in config/paths.yml
//	define('APP_ENV', getenv('APP_ENV') ?: (!empty(getenv('XDEBUG_CONFIG')) ? 'local' : 'production'));
	define('APP_ENV', getenv('APP_ENV') ?: 'production');
	define('PATH_ROOT', realpath(__DIR__ . '/../../../..'));
	define('PATH_BIN', PATH_ROOT . '/bin');
	define('PATH_CONFIG', PATH_ROOT . '/config');
	define('PATH_VAR', PATH_ROOT . '/var');
	
	require_once PATH_ROOT . '/vendor/autoload.php';
	// autoloading is registerd when  \Simple\Application is instantiated
	require_once __DIR__ . '/ClassLoader.php';
	require_once __DIR__ . '/Application.php';
	require_once __DIR__ . '/Debug.php';
	require_once __DIR__ . '/../../Simple.php';

});

//
// Global helper functions
//

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
