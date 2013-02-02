<?php
/**
 * This file is part of the PDO_Emulator package
 * @require php5-pdo, php5-mongodb (via PECL)
 * @version 0101beta
 *
 * (cc-by) VS, 2013 https://github.com/etconsilium/pdo-mongodb
 * @author Vlad A. Koltsov v.koltsov@gmail.com
 * @license BSDLv2
 *
 * @thnx2 http://sourceforge.net/projects/phppdo/ by Nikolay Ananiev <admin at devuni dot com>
 */

include 'pdo.intrf.php';
class PDO_MongoDB implements PDO_Emulator
{
	/* Constants
	-------------------------------*/
	//	@see also pdo.intrf.php
	const PARAM_REGEXP					= 6;
	const PARAM_OBJECT					= 7;
	const PARAM_OBJ						= 7;
	const PARAM_NUMBER					= 8;
	const PARAM_FLOAT					= 9;
	const PARAM_EXECUABLE				= 10;
	const PARAM_NONE					= -1;

	const DEBUG_TRANSACTION_EMULATION	= false;

	/* Public Properties
	-------------------------------*/
	/* Protected Properties
	-------------------------------*/
	protected $_client  = null;
	protected $_database  = null;
	protected $_collection = null;
	protected $_operation = null;

	protected $_statement = array();	//	все параметры в виде массива, как нужно для MongoClient
	protected $_prepared = array();		//	все параметры в виде строки

	protected $_jsql   = null;		//	подготовленные плейсхолдеры в виде строки
	protected $_query = array();	//	подготовленные плейсхолдеры в виде массива

	protected $_last_error	= null;
	protected $_last_errors = array();

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	public function __call($name, $arguments){
		if (method_exists($this->_client,$name))
			return call_user_func_array(array($this->_client,$name),$arguments);
		elseif (method_exists($this->_database,$name))
			return call_user_func_array(array($this->_database,$name),$arguments);
		elseif (method_exists($this->_collection,$name))
			return call_user_func_array(array($this->_collection,$name),$arguments);
		else
			trigger_error('method `'.$name.'` not exists');
	}

	public function __get($name){
		var_dump($name, property_exists($this,$name));
		if (property_exists($this,$name))	return $this->{$name};
		if (is_null($this->_database))	$this->selectDB($name);
		if (is_null($this->_collection))	$this->collection($name);
		return $this;
	}


	/**
	 * based on MongodbClient (PECL), but not PDO
	 * @see http://php.net/manual/ru/mongoclient.construct.php
	 *
	 * @param string $URI
	 * @param array $driver_options = array()
	 * @return this
	 */
	public function __construct($URI, $driver_options=array()) {
		$mongo = new MongoClient($URI, $driver_options);
		//	судя по ману, возможны варианты в зависимости от настроек и привелегий юзера
		if ($mongo instanceof MongoClient)
			$this->_client = $mongo;
		if ($mongo instanceof MongoDB)
			$this->_database = $mongo;
	}
	/**
	 * @return bool
	 */
	public function beginTransaction() {
		//	todo: if debug_... etc
		return true;
	}
	public function collection($cname){
		if (is_null($this->_database)) trigger_error('Mongo DB isn`t selected');
		$this->_collection = $this->_database->selectCollection($cname);
		return $this;
	}
	/**
	 * @return bool
	 */
	public function commit() {
		// TODO: уточнить про откат данных в монге
		return true;
	}
	/**
	 * @return mixed
	 */
    public function errorCode() {
        if(func_num_args() > 0) return false;
        return $this->_last_error[0];
    }

	/**
	 * @return array
	 */
	public function errorInfo() {
        if(func_num_args() > 0) return false;
        return $this->_last_error;
    }

	/**
	 * @param array $statement
	 * @return PDOStatement_MongoDB
	 */
	public function exec($statement=array()){
		if (is_null($this->_operation)) trigger_error('Mongo Operation not selected');
		$this->_statement=array_merge_recursive($this->_statement,$statement);
		return $this->_collection->{$this->_operation}($statement);
	}

	/**
	 *
	 */
	public function find($statement){
		if (is_null($this->_collection)) trigger_error('Mongo Collection not selected');
		return $this->_result = $this->_collection->find($statement);
	}

	/**
	 * Synonym selectDB()
	 */
	public function in_collection($cname){
		return $this->collection($cname);
	}

	/**
	 * @param int $attribute
	 * @return mixed
	 * @WARNING function is not working
	 */
	public function getAttribute() {
		return false;
	}
	/**
	 * @return static array
	 */
	public function getAvailableDrivers() {
		return array('mongodb');
	}
	function getPrepared(){
		return $this->_jsql;
	}
	/**
	 * @return bool
	 */
	public function inTransaction() {
		// TODO: уточнить про откат данных в монге
		return false;
	}
	/**
	 * @param string $name = NULL
	 * @return string
	 */
	public function lastInsertId(){}

	/**
	 */
	public function operation($operation){
		return $this->setOperataion($operation);
	}
	/**
	 * @param string $statement NB: This must be a valid Query statement for the MongoDB!
	 * @param array $driver_options = array()	яхз что передают в монгу
	 * @return PDOStatement
	 */
	public function prepare($statement, $driver_options=array()){
		// $statement == '$bla {bla: ? , bla: ? }'
		// список слов и операций http://docs.mongodb.org/manual/reference/operators/
		if (is_string($statement)){
			$this->_jsql = $statement;
			$this->_query = json_decode($this->_jsql, true, 512);
			//$this->_query = json_decode($this->_jsql, true, 512, JSON_BIGINT_AS_STRING);
			if ($ec=json_last_error()) trigger_error('json `'.$this->_jsql.'` decode return error code: '.$ec);
		}
		elseif (is_array($statement)){
			$this->_query = $statement;
			$this->_jsql = json_encode($this->_query, JSON_FORCE_OBJECT);
			//$this->_jsql = json_encode($this->_query, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
		}

		return $this->_statement = new PDOStatement_MongoDB($this);
	}
	/**
	 * Synonym MongoClient::selectCollection
	 */
	public function selectCollection($dbname,$collection=null){
		if (is_null($this->_client)) trigger_error('MongoClient not define yet');

		if (func_num_args()==1)
			$this->_collection = $this->_client->selectCollection($dbname);
		else
			$this->_collection = $this->selectDB($dbname)->selectCollection($collection);

		return $this;
	}
	/**
	 * Synonym MongoClient::selectDB
	 */
	public function selectDB($dbname){
		if (is_null($this->_client)) trigger_error('MongoClient not run yet');
		$this->_database = $this->_client->selectDB($dbname);
		$this->_collection = null;
		return $this;
	}

	function setOperataion($operation){
		if (is_null($this->_collection)) trigger_error('Operation not accepted');
		$this->_operation = $operation;
		return $this;
	}
	/**
	 * @param string $operation F.R.I.S.
	 * @param array $statement=array()
	 * @return PDOStatement
	 */
	public function query($operation, $statement=array()){
		if (is_null($this->_collection)) trigger_error('Mongo Collection not selected');
		else{
			switch ($operation=strtolower($operation)) {	//	F.R.I.S.
				case 'find':
				case 'select':
				case 'search':
					//break;
				case 'remove':
				case 'delete':
					//break;
				case 'insert':
					//break;
				case 'save':
				case 'update':
					//break;
					$this->_result = $this->setOperation($operation)
							->prepare($statement)
							->execute();
				default:
					trigger_error('Operation not accepted', E_USER_ERROR);
			}
			return $this->_result;
		}
	}
	/**
	 * @param string $string
	 * @param int $parameter_type = PARAM_NONE
	 * @return string
	 * @see also http://www.pvsm.ru/informatsionnaya-bezopasnost/7669 noSQL-injection
	 * @also добавлен перечислимый тип для плейсхолдера | you CAN bind multiple values to a single named parameter
	 */
	public function quote($param, $parameter_type=self::PARAM_NONE){
		switch($parameter_type)
        {
            case self::PARAM_BOOL:	//	он есть!
                return !!$param ? true : false;
            break;

            case self::PARAM_NULL:
                return !!$param ? $param : null;	//	object == typeof null
            break;

            case self::PARAM_INT:
                return (float)$param;
            break;

            case self::PARAM_STR:
                return preg_quote((string)$param);
                //return (string)escapeshellcmd($param);
            break;

			case self::PARAM_NONE:
            default:
				// WARNING! common danger
				return is_scalar($param)
						? preg_quote((string)$param)
						: (array)$param;	//	TODO PARAM_LOB && is_resourse: while(!eof($param)) read
            break;
		}
	}
	/**
	 * @param
	 * @return bool
	 */
	public function rollBack(){
		// TODO: уточнить про откат данных в монге
		return true;
	}
	/**
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute(){}
}


class PDOStatement_MongoDB implements PDOStatement_Emulator //extends Iterator
{
	/* Constants
	-------------------------------*/
	/* Public Properties
	-------------------------------*/

	public $queryString=null;
	public $queryArray=null;

	/* Protected Properties
	-------------------------------*/

	protected $_bound_params = array();
	protected $_bound_columns = array();

	protected $_ancestor	= null;
	protected $_prepared	= null;
	protected $_parameters	= array();

	protected $_last_error	= null;
	protected $_last_errors = array();
	protected $_error_code	= null;

	/* Private Properties
	-------------------------------*/
	/* Magic
	-------------------------------*/
	/* Public Methods
	-------------------------------*/
	/**
	 * @param mixed $column
	 * @param mixed &$param
	 * @param int $type=0
	 * @param int $maxlen=0
	 * @param mixed $driverdata=null
	 * @return bool
	 */
	public function bindColumn($column, &$param, $date_type=Pdo_Mongodb::PARAM_STR, $maxlen = 0, $driver_options = null){
        if($this->_result === null)
            return false;

        elseif(is_numeric($column))
        {
            if($column < 1)
            {
                //$this->_set_error(0, 'Invalid parameter number: Columns/Parameters are 1-based', 'HY093', PDO::ERRMODE_WARNING, 'bindColumn');
                return false;
            }

            $column -= 1;
        }

        $this->_bound_columns[$column] = array('column'=>$column, 'variable'=>&$param, 'type'=>$data_type);
        return true;
	}
	/**
	 * @param mixed $parameter
	 * @param mixed &$variable may be function
	 * @param int $data_type = PDO::PARAM_NONE
	 * @param int $length = 0 mixed $driver_options ]]] )
	 * @param mixed $driver_options = null
	 * @return bool
	 */
	public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = 0, $driver_options = null){
		//	NB! параметер не используется для вывода значений. не придумал как и зачем это надо

        if($parameter[0] != ':' && !is_int($parameter))
            $parameter = ':' . $parameter;

		if (is_function($variable))
			$this->_bound_params[$parameter] = array('value'=>$variable, 'type'=>Pdo_Mongodb::PARAM_EXECUABLE);
		else
			$this->_bound_params[$parameter] = array('value'=>&$variable, 'type'=>$data_type);

		return true;
	}
	/**
	 * @param mixed $parameter
	 * @param mixed $value
	 * @param int $data_type = PDO::PARAM_NONE
	 * @return
	 */
	public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR){
        if($parameter[0] != ':' && !is_int($parameter))
            $parameter = ':' . $parameter;

		$this->_bound_params[$parameter] = array('value'=>$value, 'type'=>$data_type);

		return true;
	}
	/**
	 * @return bool
	 */
	public function closeCursor(){
		//	курсоры в монго совершенно отдельная реализация итератора
		$this->queryString=null;
		$this->queryArray=null;

		$this->_bound_params = array();
		$this->_bound_columns = array();

		$this->_ancestor	= null;
		$this->_connection	= null;
		$this->_prepared	= null;
		$this->_parameters	= null;

		$this->_sql   = null;
		$this->_query = null;
		$this->_result = null;

		$this->_last_error	= null;
		$this->_last_errors = array();
		$this->_error_code	= null;

		return true;
	}
	/**
	 * @return int
	 */
	public function columnCount(){
		return count($this->_result);
	}
	/**
	 * @return bool
	 */
	public function debugDumpParams(){
		var_export(array('query'=>$this->_query
					   ,'parameters'=>$this->_parameters
					   ,'result'=>$this->_result));
		return true;
	}
	/**
	 * @return string
	 */
	public function errorCode(){
		return $this->_error_code;
	}
	/**
	 * @return array
	 */
	public function errorInfo(){
		return $this->_last_errors;
	}
	/**
	 * @param array $input_parameters = array()
	 * @return bool
	 */
	public function execute($input_parameters = array()) {
		if(!$this->_prepared)
        {
            //$this->_set_error(0, 'Invalid parameter number: statement not prepared', 'HY093', PDO::ERRMODE_WARNING, 'execute');
            return false;
        }

		$this->_parameters = array_merge($this->_parameters, $input_parameters);

		if (count($this->_parameters))
		{
			$named = array_diff_ukey($this->_parameters, array(0), function($k1,$k2){ return !is_numeric($k1); } );
			//	это проверка наличия именованных параметров, на самом деле

			if (count($named))
			{
				$input_parameters=$named;

				array_walk($input_parameters, function(&$v,$k){	if (!is_scalar($v)) $v = json_encode((array)$v); } );
				//	преобразование нескаляров. перенести в биндинг. уточнить про ресурсы

				$this->queryString = str_replace(sort(array_keys($input_parameters)), ksort($input_parameters), $this->_query);
			}

			//	pdo не смешивает, но мы смешаем
			$input_parameters=array_diff_key($this->_parameters,$named);

			$this->queryString = str_replace(array_fill(0,count($input_parameters),'?'), $input_parameters, $this->_query);
													//	count_chars, например

		}
		else{
			$this->queryString=$this->_prepared;
		}
		$this->queryArray=json_decode($this->queryString);
		//var_dump('query',$this->queryString);

		$this->_result = $this->_ancestor->exec($this->queryArray);
		//	надо поменять принцип обращения

		//	здесь проверка на ошибки

		//	возвращается MongoCursor, который имеет интерфейс итератора, см. определение interface PDOStatement
		return $this->_result;
	}
	/**
	 * @param int $fetch_style = null
	 * @param int $cursor_orientation = PDO::FETCH_ORI_NEXT
	 * @param int $cursor_offset = 0
	 * @return mixed
	 */
	public function fetch(){
		return next($this->_result);
	}
	/**
	 * @param int $fetch_style = null
	 * @param mixed $fetch_argument = null
	 * @param array $ctor_args = array()
	 * @return array
	 */
	public function fetchAll(){
		return $this->_result;
	}
	/**
	 * @param int $column_number = 0
	 * @return string
	 */
	public function fetchColumn(){}
	/**
	 * @param string $class_name = "stdClass"
	 * @param array $ctor_args = array()
	 * @return object
	 */
	public function fetchObject(){}
	/**
	 * @param int $attribute
	 * @return mixed
	 */
	public function getAttribute(){}
	/**
	 * @param int $column
	 * @return array
	 */
	public function getColumnMeta(){
		// listCollection
	}
	/**
	 * @return bool
	 */
	public function nextRowset(){}
	/**
	 * @return int
	 */
	public function rowCount(){}
	/**
	 * @param int $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function setAttribute(){}
	/**
	 * @param int $mode
	 * @return bool
	 */
	public function setFetchMode(){}

	/* Protected Methods
	-------------------------------*/
	/* Private Methods
	-------------------------------*/

	/**
	 * не упоминается в мануале
	 */
	function __construct(PDO_Emulator &$pdo) {
		$this->_ancestor = $pdo;
		$this->_prepared = $pdo->getPrepared();
		return $this;
	}

	private function _query($statemet){
		return $this->_result = $this->_ancestor->exec($statemet);
	}

    public function _set_error($code, $message, $state = 'HY000', $mode = PDO::ERRMODE_SILENT, $func = '', &$last_error = null)
    {
        if($last_error == null) $last_error =& $this->last_error;
        $last_error = array($state, $code, $message);
        $action     = ($mode >= $this->driver_options[PDO::ATTR_ERRMODE]) ? $mode : $this->driver_options[PDO::ATTR_ERRMODE];

        switch($action)
        {
            case PDO::ERRMODE_EXCEPTION:
                $e = new PDOException($this->get_error_str($code, $message, $state), $code);
                $e->errorInfo = $last_error;
                throw $e;
            break;

            case PDO::ERRMODE_WARNING:
                trigger_error($this->get_error_str($code, $message, $state, $func), E_USER_WARNING);
            break;

            case PDO::ERRMODE_SILENT:
            default:

            break;
        }
    }
    private function get_error_str($code, $message, $state, $func = '')
    {
        if($func)
        {
            if(strpos($func, '::') === false)
            {
                $class_name = 'PDO';
            }
            else
            {
                $arr        = explode('::', $func);
                $class_name = $arr[0];
                $func       = $arr[1];
            }

            if(isset($_SERVER['GATEWAY_INTERFACE']))
            {
                $prefix = $class_name . '::' . $func . '() [<a href=\'function.' . $class_name . '-' . $func . '\'>function.' . $class_name . '-' . $func . '</a>]: ';
            }
            else
            {
                $prefix = $class_name . '::' . $func . '(): ';
            }
        }
        else
        {
            $prefix = '';
        }

        if($code) return $prefix . 'SQLSTATE['.$state.'] ['.$code.'] ' . $message;
        return $prefix . 'SQLSTATE['.$state.']: ' . $message;
    }

}
?>