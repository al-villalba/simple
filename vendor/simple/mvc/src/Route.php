<?php

namespace Simple;

/**
 * Route class
 * 
 * It defines the route (controller/action) that will be used for a request
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
            throw new \Exception('Wrong routing setup');
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
			$matches = [];
			if( preg_match_all('/{(.+?)}/', $pattern, $matches) ) {
				$vars = $matches[1];
			}
			// prepare route pattern
			$tail = 0;
			$_pattern = str_replace('*', '(.*)', $pattern, $tail);
			$_pattern = '^' . str_replace($matches[0], '([^/]+?)', $_pattern) . '$';
			if( $tail ) {
				$vars[] = '__tail';
			}
			// match the pattern
			$_matches = [];
			if( preg_match('{'.$_pattern.'}', $url['path'], $_matches) ) {
//				if( count($_matches) != count($vars) + 1 ) {
//					continue;
//				}
				unset($_matches[0]);
				$pathParams = array_merge(
					['namespace' => __NAMESPACE__],
					array_combine($vars, $_matches)
				);
				array_walk($pathParams, function(&$v, $k) {
					if( in_array($k, ['namespace', 'controller', 'action']) ) {
						$v = \Simple\strCamelCase($v);
					}
					if( $k == 'action' ) {
						$v = lcfirst($v);
					}
				});
				$_route = array_merge(
					['_match' => $pattern],
					$pathParams,
					$route
				);
				$querySplitter = ['__tail' => 0, '_match' => 1,'namespace' => 2,
					'controller' => 3, 'action' => 4];
				$_route = array_merge(
					array_intersect_key($_route, $querySplitter),
					['query' => array_diff_key($_route, $querySplitter)]
				);
				
				// conclude with the query params
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
