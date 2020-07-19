<?php

namespace Simple;

/**
 * Route class
 * 
 * It defines the route (controller/action) that will be used for a request
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Route
{
	/**
	 * The route parameters
	 * @var array
	 */
	protected $_route;
	
	/**
	 * Matches request_uri against routes in config and returns the parameters
     * of the route
	 * 
	 * @return array
	 */
	public function get($path = '')
	{
		if( !empty($this->_route) ) {
			return $this->_route;
		}
		
		$app = \Simple\Application::getInstance();
		$method = $_SERVER['REQUEST_METHOD'] ?? $app['request']->getMethod();
		$routing = $app['config']['routing'][$method] ?? null;
        
        if( empty($routing) ) {
            throw new \Exception('Route not found', 404);
        }
		
		$_route = [];
		$url = parse_url($path ?: $_SERVER['REQUEST_URI']);
		if( strlen($url['path']) > 1 ) {
			$url['path'] = rtrim($url['path'], '/');
		}
		
		foreach( $routing as $pattern => $route ) {
			if( strlen($pattern) > 1 ) {
				$pattern = rtrim($pattern, '/');
			}
			// extract variables from route pattern
			$vars = [];
			$_m = [];
			if( preg_match_all('/{(.+?)}/', $pattern, $_m) ) {
				$vars = $_m[1];
			}
			// prepare route pattern
			$tail = 0;
			$_pattern = str_replace('*', '(.*)', $pattern, $tail);
			$_pattern = '^' . str_replace($_m[0], '([^/]+?)', $_pattern) . '$';
			if( $tail ) {
				$vars[] = '__tail';
			}
			// match the pattern
			$_matches = [];
			if( preg_match('{'.$_pattern.'}', $url['path'], $_matches) )
			{
				array_shift($_matches);
				if( count($_matches) != count($vars) ) {
					foreach( $route as $k => &$v ) {
						if( $v[0] === '$' ) {
							$_i = (int)substr($v, 1);
							if( isset($_matches[$_i]) ) {
								array_splice($vars, $_i, 0, [$k]);
								$v = $_matches[$_i];
							} else {
								throw new \Exception("Ruote param not matched for '$k : $v", 400);
							}
						}
					}
				}
				$pathParams = array_merge(
					['namespace' => __NAMESPACE__],
					array_combine($vars, $_matches)
				);
				$_route = array_merge(
					['_match' => $pattern],
					$pathParams,
					$route
				);
				array_walk($_route, function(&$v, $k) {
					if( in_array($k, ['namespace', 'controller', 'action']) ) {
						$v = \strCamelCase($v);
					}
					if( $k == 'action' ) {
						$v = lcfirst($v);
					}
				});
				
				// query params: tail
				
				$querySplitter = ['__tail' => 0, '_match' => 1,'namespace' => 2,
					'controller' => 3, 'action' => 4];
				$_route = array_merge(
					array_intersect_key($_route, $querySplitter),
					['query' => array_diff_key($_route, $querySplitter)]
				);
				
				if( isset($_route['__tail']) ) {
					$pairs = explode('/', $_route['__tail']);
					if( count($pairs) % 2 != 0 || empty($pairs[1]) ) {
						$_route = [];
						continue;
					}
					for( $i = 0; $i <= count($pairs) / 2; $i += 2 ) {
						if( !isset($_route['query'][$pairs[$i]]) ) {
							$_route['query'][$pairs[$i]] = $pairs[($i+1)];
						}
					}
					unset($_route['__tail']);
				}
				if( isset($url['query']) ) {
					$query = [];
					parse_str($url['query'], $query);
					$_route['query'] = array_merge(
						$_route['query'] ?? [],
						$query
					);
				}

				break;
			}
		}
		
		$this->_route = $_route;
		
		return $this->_route;
	}

	/**
	 * Generate the url from the routing parameters
	 * @TODO
	 * 
	 * @return string
	 */
	public function generateUrl()
	{
		return '';
	}

}
