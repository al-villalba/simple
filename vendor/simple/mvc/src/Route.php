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
     * @var Http\Request
     */
    protected $_request;
    
	public function __construct($request)
	{
        $this->_request = $request;
	}
	
	/**
	 * Matches request_uri against routes in config and returns the parameters
     * of the route
	 * 
	 * @return array
	 */
	public function get()
	{
		$app = \Simple\Application::getInstance();
		$routing = $app['config']['routing'];
        
        if( empty($routing) || empty($this->_request) ) {
            throw new \Exception('Wrong routing setup');
        }
		
		$_route = [];
		$url = parse_url($_SERVER['REQUEST_URI']);
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
				$_route = array_merge(
					['_match' => $pattern],
					array_combine($vars, $_matches),
					$route
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
						$query,
						$_route['query'] ?? []
					);
				}

				break;
			}
		}
		
		return $_route;
	}

}
