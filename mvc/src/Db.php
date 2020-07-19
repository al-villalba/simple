<?php

namespace Simple;

/**
 * A PDO class
 * 
 * @author Alvaro <alvaro.simplemvc@gmail.com>
 */
class Db extends \PDO
{
	/**
	 * Connect to the db
	 * 
	 * @return \PDO
	 * @throws \Exception
	 */
	public function __construct()
	{
		$app = \Simple\Application::getInstance();

		if( empty($app['config']['database']) ) {
			throw new \Exception('Wrong db configuration');
		}

		$dsn = "{$app['config']['database']['driver']}"
			. ":dbname={$app['config']['database']['name']}"
			. ";host={$app['config']['database']['host']}";
		try {
			parent::__construct($dsn,
				$app['config']['database']['user'],
				$app['config']['database']['password'], [
					\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
					\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8', time_zone = '+00:00'"
				]
			);
		} catch(\Exception $e) {
			throw new \Exception('Connection to db failed: ' . $e->getMessage());
		}
//		$this->_execute("SET NAMES 'utf8'");
	}

	/**
	 * Fetch from db by preparing the statement
	 * (SELECT)
	 * 
	 * @param string $q
	 * @param array $params
	 * @param int $fetchStyle
	 * @return array
	 */
	public function _fetchAll($q, $params = [], $fetchStyle = \PDO::FETCH_ASSOC)
	{
		try {
			if( empty($params) ) {
				$stmt = $this->query($q);
			} else {
				$stmt = $this->prepare($q);
				$this->_bindParams($stmt, $params);
				$stmt->execute();
			}
			$results = $stmt->fetchAll($fetchStyle);
		} catch(\Exception $e) {
			throw $e;
			// @todo: chage throw with return false and log error
		}
		
		// cast result types
		if( $results ) {
			foreach( $results as &$row ) {
				$this->_cast($stmt, $row);
			}
			unset($row);
		}

		return $results;
	}

	/**
	 * Fetch row from db by preparing the statement: Use this when fetching
	 * from big tables, row by row
	 * (SELECT)
	 * 
	 * @param string $q
	 * @param array $params
	 * @param int $fetchStyle
	 * @return array
	 */
	public function _fetch($q, $params = [], $fetchStyle = \PDO::FETCH_ASSOC)
	{
		try {
			if( empty($params) ) {
				$stmt = $this->query($q);
			} else {
				$stmt = $this->prepare($q);
				$this->_bindParams($stmt, $params);
				$stmt->execute();
			}
			$result = $stmt->fetch($fetchStyle);
		} catch(\Exception $e) {
			throw $e;
			// @todo: chage throw with return false and log error
		}

		// cast result types
		if( $result ) {
			$this->_cast($stmt, $result);
		}

		return $result;
	}

	/**
	 * Like _fetchAll, fetch from db preparing the statement, and restrict the
	 * output to a scalar value.
	 * (SELECT)
	 * 
	 * @param string $q
	 * @param array $params
	 * @return string
	 */
	public function _fetchScalar($q, $params = [])
	{
		try {
			$results = $this->_fetch($q, $params, \PDO::FETCH_COLUMN);
			if( empty($results) ) {
				return false;
			}
		} catch(\Exception $e) {
			throw $e;
			// @todo: chage throw with return false and log error
		}

		return $results;
	}

	/**
	 * Execute a statement preparing it previously
	 * (INSERT, UPDATE, DELETE)
	 * 
	 * @param string $q
	 * @param array $params
	 * @param int $fetchStyle
	 * @return int
	 */
	public function _execute($q, $params = [])
	{
		try {
			if( empty($params) ) {
				$count = $this->exec($q);
			} else {
				$stmt = $this->prepare($q);
				$this->_bindParams($stmt, $params);
				$stmt->execute();
				$count = $stmt->rowCount();
			}
		} catch(\Exception $e) {
			throw $e;
			// @todo: chage throw with return false and log error
		}

		return $count;
	}

	/**
	 * Bind values to parameters for the statement $stmt
	 * 
	 * @param \PDOStatement $stmt
	 * @param array $params
	 */
	protected function _bindParams(&$stmt, $params)
	{
		$isNum = array_keys($params) === range(0, count($params) - 1);
		foreach( $params as $k => &$v ) {
			if( $isNum ) $k += 1;
			if( $v === null ) {
				$stmt->bindValue($k, $v, \PDO::PARAM_NULL);
			} elseif( is_int($v) ) {
				$stmt->bindValue($k, $v, \PDO::PARAM_INT);
			} else {
				$stmt->bindValue($k, $v);
			}
		}
	}

	/**
	 * Cast type of row columns according to their types in the db
	 * 
	 * @param \PDOStatement $stmt
	 * @param array $row
	 */
	protected function _cast($stmt, &$row)
	{
		// helper function to cast a scalar
		$cast = function($type, $scalar) {
			switch( $type ) {
				case 'TINY':
				case 'LONG':
				case 'LONGLONG':
					return intval($scalar);
				case 'DOUBLE':
				case 'DECIMAL':
				case 'NEWDECIMAL':
					return floatval($scalar);
//				default:
//					if( !in_array($type, ['VAR_STRING', 'TIMESTAMP', 'BLOB', 'TIME']) )
//						\Debug::fpc( $type );
			}

			return $scalar;
		};

		// row may be scalar (_fetchScalar)
		if( is_scalar($row) ) {
			$row = $cast($stmt->getColumnMeta(0)['native_type'], $row);
			return;
		}

		$_j = 0;
		foreach( $row as &$_v ) {
			if( $_v === null || $_v === '' ) {
				$_j++;
				continue;
			}
			$_v = $cast($stmt->getColumnMeta($_j)['native_type'], $_v);
			$_j++;
		}
		unset($_v);
		
		return;
	}

}
