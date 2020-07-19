<?php

namespace Simple;

/**
 * Contains the necessary resources to handle requests. It's the duty of the
 * backend developer to load only those resources that are necessary for a
 * concrete request. Requests may come from the command line or from the web
 * server interface (cgi).
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT
 */
class Application implements \ArrayAccess
{
	//const VERSION = '0.1.20190101';
	const VERSION = '1.0.20200301';

	/**
	 * Singleton instance
	 * @var Application
	 */
	public static $instance;

	/**
	 * Container attributes (ArrayAccess): It contains dependencies
	 * @var array
	 */
	protected $_props = [];

	/**
	 * Only callable from self class (Singleton instance)
	 */
	protected function __construct()
	{
		$this->env = APP_ENV;

		$this->_loadConfig()
			->_registerThrowableHandler()
			->_registerAutoload();

		if( !empty($this->config['app']['date']['timezone']) ) {
			date_default_timezone_set($this->config['app']['date']['timezone']);
		}
	}

	/**
	 * __clone is disabled (Singleton pattern)
	 */
	public function __clone() 
	{
		throw new \Exception('Singleton clonation not allowed');
	}

	/**
	 * dummy __sleep (Singleton pattern)
	 */
	public function __sleep() 
	{
		return [];
	}

	/**
	 * __wakeup is disabled (Singleton pattern)
	 */
	public function __wakeup() 
	{
		throw new \Exception('Singleton unserialization not allowed');
	}

	/**
	 * Get the singleton object
	 * 
	 * @return Application
	 */
	public static function getInstance()
	{
		if( !empty(self::$instance) ) {
			return self::$instance;
		}

		self::$instance = new static(); // allow inheritance

		return self::$instance;
	}

	/*
	 * Object/Array access (preference for object access)
	 */
	public function __set(string $key, $value) : void
	{
		$this->_props[$key] = $value;
	}
	public function offsetSet($key, $value) : void
	{
		$this->{$key} = $value;
	}

	public function __get(string $key)
	{
		if( !isset($this->_props[$key]) ) {
			// try to autoload class using naming convention (only in /src)
			foreach( $this->config['autoload'] as $ns => $path ) {
				$className = "$ns\\" . strCamelCase($key);
				if( substr(rtrim($path, '/'), -4) == '/src' &&
					class_exists($className)
				) {
					if( is_callable([$className, 'factory']) ) {
						$this->_props[$key] = $className::factory();
					} else {
						$this->_props[$key] = new $className();
					}
					return $this->_props[$key];
				}
			}
			return null;
		}

//		$isCallable = is_callable($this->_props[$key]) ||
//			(is_object($this->_props[$key]) && is_callable([$this->_props[$key], '__invoke']));

		return (is_callable($this->_props[$key]) && !is_scalar($this->_props[$key])) ?
			$this->_props[$key]($this) :
			$this->_props[$key];
	}
	public function offsetGet($key)
	{
		return $this->{$key};
	}

	public function __isset(string $key) : bool
	{
		return isset($this->_props[$key]);
	}
	public function offsetExists($key) : bool
	{
		return isset($this->{$key});
	}

	public function __unset(string $key) : void
	{
		unset($this->_props[$key]);
	}
	public function offsetUnset($key) : void
	{
		unset($this->{$key});
	}

	/**
	 * Array representation of the object
	 * 
	 * @return array
	 */
	public function toArray() 
	{
		$array = [];
		
		foreach( $this->_props as $attrName => $attrValue ) {
			if( is_callable($attrValue) ) {
				$attrValue($this);
			}
			// case scalar
			if( is_scalar($this->_props[$attrName]) ) {
				$array[$attrName] = $this->_props[$attrName];
				continue;
			}
			// case array
			if( is_array($this->_props[$attrName]) &&
				!in_array($attrName, ['config', 'similarWeeks'])
			) {
				if( is_callable($this->_props[$attrName]) ) {
					$array[$attrName] = $this->_props[$attrName]($this);
					continue;
				}
				$array[$attrName] = array_map(function($_v) {
					if( is_object($_v) && is_callable([$_v, 'toArray']) ) {
						return $_v->toArray();
					}
					return $_v;
				}, $this->_props[$attrName]);
				continue;
			}
			// case object controller
			if( $attrName == 'controller' ) {
				$array[$attrName] = get_class($this->_props[$attrName]);
				continue;
			}
			// case object
			if( is_object($this->_props[$attrName]) &&
				is_callable([$this->_props[$attrName], 'toArray'])
			) {
				$array[$attrName] = $this->_props[$attrName]->toArray();
				continue;
			}
//			if( is_object($this->_props[$attrName]) && is_callable([$this->_props[$attrName], '__invoke']) ) {
//				$array[$attrName] = $this->_props[$attrName]($this);
//				continue;
//			}
		}
		
		return $array;
	}

	/**
	 * Load service config
	 * 
	 * @return Application
	 */
	protected function _loadConfig()
	{
		$config = [];

		// read config files (global)
		foreach( glob(PATH_CONFIG . '/*.json') as $cfgPath ) {
			$config[basename($cfgPath, '.json')] =
				json_decode(file_get_contents($cfgPath), true);
		}
		// overwrite configs with enviromental values
		if( is_dir(PATH_CONFIG . "/{$this->env}") ) {
			foreach( glob(PATH_CONFIG . "/{$this->env}/*.json") as $cfgPath ) {
				$config[basename($cfgPath, '.json')] = array_replace_recursive(
					$config[basename($cfgPath, '.json')] ?? [],
					json_decode(file_get_contents($cfgPath), true)
				);
			}
		}

		// replace entries like %.*% with environment values
		array_walk_recursive($config, function(&$v) {
			$m = [];
			if( preg_match('/%(.*)%/', $v, $m) ) {
				$env = getenv($m[1]);
				if( empty($env) ) {
					@$env = constant($m[1]);
				}
				if( $env ) {
					$v = preg_replace('/%.*%/', $env, $v);
				} else {
					throw new \Exception(
						"Environment variable '" . $m[1] . "' is not defined");
				}
			}
		});
		
		$this->config = $config;

		return $this;
	}

	/**
	 * Initialise resource db
	 * 
	 * @return Application
	 */
	protected function _registerAutoload()
	{
		$nsPaths = [];
		foreach( $this->config['autoload'] as $ns => $path ) {
			$nsPaths[$ns] = $path;
		}
		
		$classLoader = new ClassLoader($nsPaths);

//		set_include_path(get_include_path().':'.realpath(__DIR__));
		spl_autoload_extensions(".php,.inc");
		spl_autoload_register( [$classLoader, 'loadClass'] );

		return $this;
	}

	/**
	 * Inject Logger in Throwables
	 * @see https://www.php.net/manual/en/function.set-exception-handler.php
	 * 
	 * @return Application
	 */
	protected function _registerThrowableHandler()
	{
		if( !$this->config['log']['log_errors'] ) {
			return $this;
		}

		// Exception/Error handler
		$throwableHandler = function(\Throwable $e) {
			$msg = $e->__toString();
			\Simple\Logger::error($msg);
			// falling back to php's standard exception handler doesn't work!
//			restore_exception_handler();
//			set_exception_handler(null);
//			throw $e;
			if( $this->sapi_name != 'cli' ) {
				$msg = "<pre>$msg</pre>";
			}
			if( (bool)intval(ini_get('display_errors')) ||
				strtolower(ini_get('display_errors')) === 'on' ||
				strtolower(ini_get('display_errors')) === 'stderr'
			) {
				if( strtolower(ini_get('display_errors')) === 'stderr' ) {
					file_put_contents('php://stderr', $msg);
				} else {
					echo $msg;
				}
			}

			exit($e->getCode());
		};
		
		// register handler

		set_error_handler(function($errno, $errstr, $errfile, $errline) use($throwableHandler) {
			$throwableHandler( new \ErrorException($errstr, 500, $errno, $errfile, $errline) );
			// exit in handler
		});

		set_exception_handler($throwableHandler);

		return $this;
	}

	/**
	 * Run the application
	 * 
	 * @return int
	 * @throws \Exception
	 */
	public function run()
	{
		// load controller/command class and define action metohd
		list($controller, $action) = $this->initRequest();
		if( !class_exists($controller) || !method_exists($controller, $action) ) {
			Logger::info("[$controller, $action] 404 not found");
			$response = new Http\Response('Not Found', 404);
			return $this->end($response);
		}

		// do not overwrite unit test's Mock_* controller
		if( !isset($this->controller) || substr(get_class($this->controller), 0, 5) != 'Mock_' ) {
			$this->controller = new $controller();
		}
		$this->action = $action;

		$this->controller->_beforeAction($this->action);
		if( isset($this->response) && $this->response instanceof ResponseInterface ) {
			/** @var ResponseInterface $response defined in _before() */
			$response = $this->response;
		} else {
			/** @var ResponseInterface $response */
			$response = $this->controller->{$this->action}();
		}
		$this->controller->_afterAction($this->action);

		if( ! $response instanceof ResponseInterface ) {
			throw new \Exception(
				(is_object($this->controller) ? get_class($this->controller) : '')
				. "/{$this->action}()" . " must return an instance of '"
				. ResponseInterface::class
				. "'. '" . get_class($response) . "' returned.");
		}

		return $this->end($response);
	}

	/**
	 * Define controller/command class and action method
	 */
	public function initRequest()
	{
		$this->route = new Route();
		
		$this->sapi_name = $this->sapi_name ?? php_sapi_name();
		if( $this->sapi_name == 'cli' ) {
			// cli
			$this->request = RequestFactory::create();
			$cmd = explode('/', trim($this->request->getParam(0), '/'));
			$route = $this->route->get('/' . __NAMESPACE__
				. '/' . \Simple\strCamelCase($cmd[0])
				. '/' . (isset($cmd[1]) ? lcfirst(\Simple\strCamelCase($cmd[1])) : 'run')
			);
			// @todo actual __NAMESPACE__ (not \Simple) must be included
			if( empty($route) ) {
				$route = $this->route->get('/' . \Simple\strCamelCase($cmd[0])
					. '/' . (isset($cmd[1]) ? lcfirst(\Simple\strCamelCase($cmd[1])) : 'run')
				);
			}
			/** @see http://php.net/manual/en/language.namespaces.dynamic.php */
			$controller = "{$route['namespace']}\\Command\\{$route['controller']}";
		} else {
			// cgi, apache2handler, etc.
			$route = $this->route->get();
			/** @see http://php.net/manual/en/language.namespaces.dynamic.php */
			$controller = "{$route['namespace']}\\Controller\\{$route['controller']}";
			$this->request = RequestFactory::create();
		}

		return [$controller, $route['action']];
	}

	/**
	 * Echo response and return exit status.
	 * run() must end with end()
	 * 
	 * @param ResponseInterface $response
	 * @return int
	 */
	public function end($response)
	{
		$response->output();

		return $this->exitStatus ?? 0;
	}

}
