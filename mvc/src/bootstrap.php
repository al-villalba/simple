<?php

namespace Simple;

/**
 * Init app essentials.
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
call_user_func(function()
{
	// define environment
	define('APP_ENV', getenv('APP_ENV') ?: 'production');
	define('PATH_ROOT', realpath(__DIR__ . '/../../../..'));
	
	require_once PATH_ROOT . '/vendor/autoload.php';
	require_once __DIR__ . '/functions.php';
	require_once __DIR__ . '/Application.php';
	require_once __DIR__ . '/../../Simple.php';

});
