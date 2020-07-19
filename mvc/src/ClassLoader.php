<?php

namespace Simple;

/**
 * Scope isolated include (prevents access to $this/self from included files)
 */
function requireOnce($file)
{
    require_once $file;
}

/**
 * A ClassLoader to be used by spl_autoload_register
 *
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT
 */
class ClassLoader
{
	/**
	 * Paths to the namespaces
	 * @var array
	 */
	protected $_nsPaths;
	
	/**
	 * Constructor
	 * 
	 * @param array $nsPaths
	 */
	public function __construct($nsPaths)
	{
		$this->_nsPaths = $nsPaths;
	}

    /**
     * Loads the given class or interface. This is to be used in
	 * spl_autoload_register()
     *
     * @param string $className
     * @return bool|null true if loaded, null otherwise
     */
    public function loadClass($className)
    {
        if ($file = $this->findFile($className)) {
            requireOnce($file);

            return true;
        }
    }
	
    /**
     * Return the path to the file where the class is defined or false if not
	 * found
     *
     * @param string $className
     * @return string|false
     */
    public function findFile($className)
    {
		$_classNs = explode('\\', $className);
		array_splice($_classNs, -1);
		$classNs = implode('\\', $_classNs);
		if( empty($classNs) ||
			!in_array($_classNs[0], array_keys($this->_nsPaths))
		) {
			return false;
		}

		foreach( explode(',', spl_autoload_extensions()) as $ext )
		{
			foreach( $this->_nsPaths as $ns => $path ) {
				$classPath = str_replace("\\", "/",
					str_replace($ns, $path, $className . $ext));
				if( is_readable($classPath) ) {
					return $classPath;
				}
			}
		}

		return false;
	}

}
