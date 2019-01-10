<?php

namespace Simple;

/**
 * Http class
 * 
 * It handles the http protocol
 */
class Http
{
	const BODY_ENCODE = 1;
	const BODY_DECODE = 2;
	
	/**
	 * The http status code
	 * @var int
	 */
	protected $_code;
	
	/**
	 * The first line of the http headers
	 * @var string
	 */
	protected $_statusLine;
	
	/**
	 * The http headers
	 * @var array
	 */
	protected $_headers;
	
	/**
	 * The http body decoded
	 * @var string|array
	 */
	protected $_body;
	
	/**
	 * The http body raw (may be encoded and/or gzipped)
	 * @var string
	 */
	protected $_bodyRaw;
	
	/**
	 * Constructor
	 * 
	 * It can be passed with 1, 2 or 3 arguments.
	 * If two arguments are passed, the second argument can be either the http
	 * headers or the http status code.
	 * If three arguments are passed, the second and third aguments can be used
	 * for both headers or status code.
	 * 
	 * @param mixed $body
	 * @param array|string|int $headers
	 * @param int|array $code
	 */
	public function __construct($body = '', $headers = [], $code = 200)
	{
		// prepare args
		$this->_bodyRaw    = $body;
		$this->_body       = null;
		$this->_statusLine = '';
		$this->_headers    = [];
		$this->_code       = $code;
		
		if( is_string($headers) && (string)intval($headers) === $headers ) {
			// case $headers like "200"
			$headers = intval($headers);
		}
		if( is_int($headers) ) {
			// case $headers like 200
			$this->_code = $headers;
		}
		if( is_string($headers) || is_array($headers) ) {
			// headers given as string or array
			$this->setHeaders($headers);
		}
	}
	
	/**
	 * Cast to string
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return ($this->_statusLine ? "{$this->_statusLine}\r\n" : '')
			. $this->_assoc2headers($this->getHeaders(), true)
			. "\r\n\r\n"
			. $this->getBody(self::BODY_ENCODE);
	}
	
	/**
	 * Get code
	 * 
	 * @return int
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * Get all headers
	 * 
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->_headers;
	}
	
	/**
	 * Set headers as an assoc
	 * 
	 * $headers may contain the status as the first line
	 * 
	 * @param array|string $headers
	 * @return \Simple\Http
	 */
	public function setHeaders($headers)
	{
		$assoc = $this->_headers2assoc($headers);
		$this->_statusLine = $assoc[0];
		$this->_headers    = array_slice($assoc, 1);
		
		return $this;
	}
	
	/**
	 * Get a header
	 * 
	 * @param string $header
	 * @return string|array
	 */
	public function getHeader($header)
	{
		$value = null;
		foreach( $this->_headers as $h => $v ) {
			if( strtolower($h) == strtolower($header) ) {
				$value = $v;
				break;
			}
		}
		
		return $value;
	}

	/**
	 * Set a header, or unset if $value === null (regardless of $append)
	 * 
	 * It overwrites or appends the value according to $append.
	 * 
	 * @param string $header
	 * @param string $value
	 * @param bool $append
	 * @return \Simple\Http
	 */
	public function setHeader($header, $value, $append = false)
	{
		foreach( $this->_headers as $h => $v ) {
			if( strtolower($h) == strtolower($header) ) {
				if( $append ) {
					$value = "$v,$value";
				}
				unset($this->_headers[$h]);
				break;
			}
		}
		
		if( $value !== null ) {
			$this->_headers[ucwords($header, "-")] = $value;
		}
		
		return $this;
	}

	/**
	 * Unset a header
	 * 
	 * @param string $header
	 * @return \Simple\Http
	 */
	public function unsetHeader($header)
	{
		return $this->setHeader($header, null);
	}

	/**
	 * Get body
	 * 
	 * @param int $action How to handle body content: Encode it or decode it
	 * @return string
	 */
	public function getBody($action = self::BODY_DECODE)
	{
		// trivial cases
		if( $this->_body !== null ) {
			return $this->_body;
		}
		if( empty($this->_bodyRaw) ) {
			$this->_body = $this->_bodyRaw;
			return $this->_body;
		}

		$this->_body = $this->_bodyRaw;

		// gzipped wrap
		if( $this->getHeader('Content-Encoding') == 'gzip' ) {
			if( $action == self::BODY_DECODE ) {
				$this->_body = gzdecode($this->_bodyRaw);
			}
		}

		// encoded body
		if( preg_match('{application/x-www-form-urlencoded}', $this->getHeader('Content-Type')) ) {
			if( $action == self::BODY_DECODE ) {
				parse_str($this->_body, $this->_body);
			} else {
				$this->_body = http_build_query($this->_body);
			}
		}
		if( preg_match('{application/json}', $this->getHeader('Content-Type')) ) {
			if( $action == self::BODY_DECODE ) {
				$this->_body = json_decode($this->_body, true);
			} else {
				$this->_body = json_encode($this->_body);
			}
		}
		
		// gzipped wrap
		if( $this->getHeader('Content-Encoding') == 'gzip' ) {
			if( $action == self::BODY_ENCODE ) {
				$this->_body = gzencode($this->_body);
			}
		}


		// return
		return $this->_body;
	}

	/**
	 * Parse $headers, which may be given as array or in a whole as string,
	 * returning an assoc with all header definitions.
	 * 
	 * If $headers is a string, each header maybe separated by "\r\n" or "\n"
	 * 
	 * @param array|string $headers
	 * @return array
	 */
	protected function _headers2assoc($headers)
	{
		if( is_string($headers) ) {
			$headers = explode("\n", strtr($headers, ["\r\n" => "\n"]));
		}
		
		// return if headers is empty or associative array
		if( empty($headers) ||
			array_keys($headers) !== range(0, count($headers) - 1)
		) {
			if( !isset($headers[0]) ) {
				$headers = array_merge([''], $headers);
			}
			return $headers;
		}
		
		// define assoc
		$assoc = [''];
		foreach( $headers as $i => $h ) {
			$_h = explode(":", $h, 2);
			if( count($_h) != 2 ) {
				if( $i == 0 ) {
					$assoc[0] = $h;
				}
				continue;
			}
			
			$_h[0] = ucwords($_h[0], "-");
			if( isset($assoc[$_h[0]]) ) {
				// put multiple occurrences in an array
				if( is_array($assoc[$_h[0]]) ) {
					$assoc[$_h[0]][] = trim($_h[1]);
				} else {
					$assoc[$_h[0]] = [$assoc[$_h[0]], trim($_h[1])];
				}
			} else {
				// single occurrence
				$assoc[$_h[0]] = trim($_h[1]);
			}
		}
		
		// reduce multiple occurrences
		foreach( $assoc as $h => &$v ) {
			if( is_array($v) ) {
				array_walk($v, function(&$_v) {$_v = trim($_v);});
				$v = implode(',', $v);
			}
		}
		
		return $assoc;
	}

	/**
	 * Inverse function of _headers2assoc:
	 * Build headers from an assoc
	 * 
	 * @param array $assoc
	 * @param bool $asString
	 * @return array|string
	 */
	protected function _assoc2headers($assoc, $asString = false)
	{
		$headers = [];
		if( isset($assoc[0]) ) {
			$headers[] = $assoc[0];
			unset($assoc[0]);
		}
		foreach( $assoc as $h => $v ) {
//			if( is_array($v) ) {
//				// multiple occurrences
//				foreach( $v as $_v ) {
//					$headers[] = "$h: $_v";
//				}
//			} else {
				// single occurrence
				$headers[] = "$h: $v";
//			}
		}
		
		if( $asString ) {
			// reduce to string
			return implode("\r\n", $headers);
		}
		// return as numeric array
		return $headers;
	}

}
