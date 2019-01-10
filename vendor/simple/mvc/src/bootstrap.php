<?php

namespace Simple;

/*
 * Init the php essentials to ease the initializtion of the app
 * Run within a local function
 */
call_user_func(function() {
	
	// define essential paths. Further paths are defined in config/paths.yml
	define('APP_ENV', getenv('APP_ENV') ?: (!empty(getenv('XDEBUG_CONFIG')) ? 'local' : 'production'));
	define('PATH_ROOT', realpath(__DIR__ . '/../../../..'));
	define('PATH_CONFIG', PATH_ROOT . '/config');
	
	// init autoloading, src and controllers folders of:
	// __DIR__ . '/..'
	// PATH_ROOT . '/mvc'
	set_include_path(get_include_path().':'.realpath(__DIR__));
	spl_autoload_extensions(".php,.inc");
	spl_autoload_register( function($className)
	{
		$ns2path = [
			__NAMESPACE__ . '\Command'    => [PATH_ROOT . '/commands',
			                                  __DIR__ . '/../../commands'],
			__NAMESPACE__ . '\Controller' => [PATH_ROOT . '/mvc/controllers',
			                                  __DIR__ . '/../controllers'],
			__NAMESPACE__                 => [PATH_ROOT . '/mvc/src',
			                                  __DIR__],
		];
		
		$_classNs = explode('\\', $className);
		array_splice($_classNs, -1);
		$classNs = implode('\\', $_classNs);
		if( empty($classNs) ) {
			return;
		}
		
		foreach( explode(',', spl_autoload_extensions()) as $ext )
		{
			// link namespaces with directories to search in
			foreach( $ns2path as $ns => $nsPaths ) {
				
				$classPath = $className . $ext;
				
				foreach( (array)$nsPaths as $nsPath ) {
					$path = str_replace("\\", "/", 
						str_replace("$ns", "$nsPath", $classPath));
					if( is_readable($path) ) {
						require_once $path;
						return;
					}
				}
			}
		}
	});
	
});

require_once PATH_ROOT . '/vendor/autoload.php';
require_once __DIR__ . '/Debug.php';

//
// Global functions
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
