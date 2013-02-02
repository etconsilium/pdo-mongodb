<?php
/**
 * @package PDO_Emulator
 * @see http://php.net/manual/ru/class.pdo.php
 * @require PDO
 *
 * (cc-by) VS, 2013 https://github.com/etconsilium/pdo-mongodb
 * @author Vlad A. Koltsov v.koltsov@gmail.com
 * @license BSDLv2
 */

/**
 * interface PDO, complete copy from class PDO
 */
interface PDO_Emulator
{
    const PARAM_BOOL           = 5;
    const PARAM_NULL           = 0;
    const PARAM_INT            = 1;
    const PARAM_STR            = 2;
    const PARAM_LOB            = 3;
    const PARAM_STMT           = 4;
	//const PARAM_INPUT_OUTPUT   = -2147483648;

	/**
	 * @param string $dsn
	 * @param string $username = ''
	 * @param string $password = ''
	 * @param array $driver_options = array()
	 * @return ?
	 */
	public function __construct($URI, $driver_options=array());
	/**
	 * @return bool
	 */
	public function beginTransaction();
	/**
	 * @return bool
	 */
	public function commit();
	/**
	 * @return mixed
	 */
	public function errorCode();
	/**
	 * @return array
	 */
	public function errorInfo();
	/**
	 * @param string $statement
	 * @return int
	 */
	public function exec($statement);
	/**
	 * @param int $attribute
	 * @return mixed
	 */
	public function getAttribute();
	/**
	 * @return static array
	 */
	public function getAvailableDrivers();
	/**
	 * @param
	 * @return
	 */
	public function inTransaction();
	/**
	 * @param string $name = NULL
	 * @return string
	 */
	public function lastInsertId();
	/**
	 * @param string $statement
	 * @param array $driver_options = array()
	 * @return PDOStatement
	 */
	public function prepare($statement, $driver_options);
	/**
	 * @param string $statement
	 * @return PDOStatement
	 */
	public function query($statement);
	/**
	 * @param string $string
	 * @param int $parameter_type = PDO::PARAM_STR
	 * @return string
	 */
	public function quote($param, $parameter_type);
	/**
	 * @return bool
	 */
	public function rollBack();
	/**
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute();
}

/**
 * interface PDOStatement, complete copy from class PDO
 */
interface PDOStatement_Emulator //extends Traversable
{
	//public $queryString=null;
	/**
	 * @param mixed $column
	 * @param mixed &$param
	 * @param int $type=0
	 * @param int $maxlen=0
	 * @param mixed $driverdata=null
	 * @return bool
	 */
	public function bindColumn($column, &$param, $date_type, $maxlen, $driver_options);
	/**
	 * @param mixed $parameter
	 * @param mixed &$variable
	 * @param int $data_type = PDO::PARAM_STR
	 * @param int $length = 0 mixed $driver_options ]]] )
	 * @param mixed $driver_options = null
	 * @return bool
	 */
	public function bindParam($parameter, &$variable, $data_type, $length, $driver_options);
	/**
	 * @param mixed $parameter
	 * @param mixed $value
	 * @param int $data_type = PDO::PARAM_STR
	 * @return
	 */
	public function bindValue($parameter, $value, $data_type);
	/**
	 * @return bool
	 */
	public function closeCursor();
	/**
	 * @return int
	 */
	public function columnCount();
	/**
	 * @return bool
	 */
	public function debugDumpParams();
	/**
	 * @return string
	 */
	public function errorCode();
	/**
	 * @return array
	 */
	public function errorInfo();
	/**
	 * @param array $input_parameters = array()
	 * @return bool
	 */
	public function execute($input_parameters);
	/**
	 * @param int $fetch_style = null
	 * @param int $cursor_orientation = PDO::FETCH_ORI_NEXT  int $cursor_offset = 0 ]]]
	 * @param int $cursor_offset = 0
	 * @return mixed
	 */
	public function fetch();
	/**
	 * @param int $fetch_style = null
	 * @param mixed $fetch_argument = null
	 * @param array $ctor_args = array()
	 * @return array
	 */
	public function fetchAll();
	/**
	 * @param int $column_number = 0
	 * @return string
	 */
	public function fetchColumn();
	/**
	 * @param string $class_name = "stdClass"
	 * @param array $ctor_args = array()
	 * @return object
	 */
	public function fetchObject();
	/**
	 * @param int $attribute
	 * @return mixed
	 */
	public function getAttribute();
	/**
	 * @param int $column
	 * @return array
	 */
	public function getColumnMeta();
	/**
	 * @return bool
	 */
	public function nextRowset();
	/**
	 * @return int
	 */
	public function rowCount();
	/**
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute();
	/**
	 * @param int $mode
	 * @return bool
	 */
	public function setFetchMode();
}
?>