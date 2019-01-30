<?php

namespace Simple;

/**
 * Contains the necessary resources to handle requests. It's the duty of the
 * backend developer to load only! those resources that are necessary for a
 * concrete request. Requests may come from the command line or from the web
 * server interface (cgi).
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Application implements \ArrayAccess
{
	const VERSION = '0-20190101';

	/**
	 * Singleton instance
	 * @var Application
	 */
	public static $instance;

	/**
	 * Container attributes (ArrayAccess): It contains dependencies
	 * @var array
	 */
	protected $_attributes = [];

	/**
	 * Only callable from self class (Singleton instance)
	 */
	protected function __construct()
	{
		$this['env'] = APP_ENV;

		$this->_loadConfig()
			->_autoloadRegister();
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

	/**
	 * ArrayAccess' offsetSet
	 * 
	 * @param string|int $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value)
	{
		$this->_attributes[$key] = $value;
	}

	/**
	 * ArrayAccess' offsetGet
	 * 
	 * @param string|int $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		if( !isset($this->_attributes[$key]) ) {
			// try to autoload class using naming convention (only models)
			foreach( $this['config']['autoload'] as $ns => $path ) {
				$className = "$ns\\" . strCamelCase($key);
				if( substr(rtrim($path, '/'), -4) == '/src' &&
					class_exists($className)
				) {
					if( is_callable([$className, 'factory']) ) {
						$this->_attributes[$key] = $className::factory();
					} else {
						$this->_attributes[$key] = new $className();
					}
					return $this->_attributes[$key];
				}
			}
			return null;
		}

		$isCallable = is_callable($this->_attributes[$key]) ||
			(is_object($this->_attributes[$key]) &&
				is_callable($this->_attributes[$key], '__invoke'));

		return $isCallable ?
			$this->_attributes[$key]($this) :
			$this->_attributes[$key];
	}

	/**
	 * ArrayAccess' offsetSet
	 * 
	 * @param string|int $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return isset($this->_attributes[$key]);
	}

	/**
	 * ArrayAccess' offsetSet
	 * 
	 * @param string|int $key
	 */
	public function offsetUnset($key)
	{
		unset($this->_attributes[$key]);
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
		if( is_dir(PATH_CONFIG . "/{$this['env']}") ) {
			foreach( glob(PATH_CONFIG . "/{$this['env']}/*.json") as $cfgPath ) {
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
		$this['config'] = $config;

		return $this;
	}

	/**
	 * Initialise resource db
	 * 
	 * @return Application
	 */
	protected function _autoloadRegister()
	{
		$nsPaths = [];
		foreach( $this['config']['autoload'] as $ns => $path ) {
			$nsPaths[$ns] = $path;
		}

//		set_include_path(get_include_path().':'.realpath(__DIR__));
		spl_autoload_extensions(".php,.inc");
		spl_autoload_register( function($className) use ($nsPaths)
		{
			$_classNs = explode('\\', $className);
			array_splice($_classNs, -1);
			$classNs = implode('\\', $_classNs);
			if( empty($classNs) || !in_array($_classNs[0], array_keys($nsPaths)) ) {
				return;
			}

			foreach( explode(',', spl_autoload_extensions()) as $ext )
			{
				foreach( $nsPaths as $ns => $path ) {
					$classPath = str_replace("\\", "/",
						str_replace($ns, $path, $className . $ext));
					if( is_readable($classPath) ) {
						require_once $classPath;
						return;
					}
				}
			}
		});

		return $this;
	}

	/**
	 * Run the application
	 * 
	 * @return Application
	 * @throws \Exception
	 */
	public function run()
	{
		try {
			// load controller/command class and define action metohd
			list($controller, $action) = $this->initRequest();

			// do not overwrite unit test's Mock_* controller
			if( !isset($this['controller']) || substr(get_class($this['controller']), 0, 5) != 'Mock_' ) {
				$this['controller'] = new $controller();
			}
			$this['action'] = $action;
		} catch( \Exception $e ) {
			// @todo ???
			throw $e;
		} catch( \Error $e ) {
			// 404 exception
			// @todo: log detail info, throw a 404
			throw $e;
		}

		$this['controller']->_beforeAction($this['action']);
		if( isset($this['response']) && $this['response'] instanceof ResponseInterface ) {
			/** @var ResponseInterface $response defined in _before() */
			$response = $this['response'];
		} else {
			/** @var ResponseInterface $response */
			$response = $this['controller']->{$this['action']}();
		}
		$this['controller']->_afterAction($this['action']);

		if( ! $response instanceof ResponseInterface ) {
			throw new \Exception(
				(is_object($this['controller']) ? get_class($this['controller']) : '')
				. "/{$this['action']}()" . " must return an instance of '"
				. ResponseInterface::class
				. "'. '" . get_class($response) . "' returned.");
		}

		$response->output();

		return $this;
	}

	/**
	 * Define controller/command class and action method
	 */
	public function initRequest()
	{
		$this['route']   = new Route();
		
		$sapiName = $this['sapi_name'] ?? php_sapi_name();
		if( $sapiName == 'cli' ) {
			// cli
			$this['request'] = RequestFactory::create();
			$cmd = explode('/', trim($this['request']->getParam(0), '/'));
			$route = $this['route']->get('/' . __NAMESPACE__
				. '/' . \Simple\strCamelCase($cmd[0])
				. '/' . (isset($cmd[1]) ? lcfirst(\Simple\strCamelCase($cmd[1])) : 'run')
			);
			/** @see http://php.net/manual/en/language.namespaces.dynamic.php */
			$controller = "{$route['namespace']}\\Command\\{$route['controller']}";
			if( !class_exists($controller) ) {
				$controller = "{$route['namespace']}\\Controller\\{$route['controller']}";
			}
		} else {
			// cgi
			$route = $this['route']->get();
			/** @see http://php.net/manual/en/language.namespaces.dynamic.php */
			$controller = "{$route['namespace']}\\Controller\\{$route['controller']}";
			$this['request'] = RequestFactory::create();
		}

		return [$controller, $route['action']];
	}

}
