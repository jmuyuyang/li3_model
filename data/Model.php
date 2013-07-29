<?php
/*
* @yuyang
*/
namespace li3_model\data;

use lithium\util\Validator;

class Model{

	public $validator;

	protected static $_adapter = 'MySql';
	protected static $_instances = array();
	protected static $_adapterPool = array();
	protected static $_defaultMeta = array();
	protected static $_meta  = array(
		'connection' => 'default',
		'source' => __CLASS__,
		'key' => 'id',
		'apart_options' => array()
	);

	public static function instance(){
		$class = get_called_class();
		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
		}
		return static::$_instances[$class];
	}

	public static function db($name){
		return static::adapter($name);
	}

	public static compute($apart_id){
		static::$_meta['source'] .= static::_computeTableId($apart_id);
	}

	public static function meta($item = NULL){
		if($item){
			return static::$_meta[$item];
		}
		return static :: $_meta;
	}

	public static function adapter($name = NULL){
		$name = $name?:static::$_meta['connection'];
		if(!isset(static::$_adapterPool[$name])){
			$adapter = "li3_model\data\db\\".static::$_adapter."\Adapter";
			static::$_adapterPool[$name] = new $adapter($name);
		}
		static::fixDefault($name);
		return static::$_adapterPool[$name];
	}

	public static function fixDefault($name){
		isset(static::$_adapterPool[$name]) && static::$_adapterPool[$name]->init(static::$_meta);
		if(static::$_defaultMeta){
			static::$_meta = static::$_defaultMeta;
			static::$_defaultMeta = array();
		}
	}
     	
	public function query(){
		$args = func_get_args();
		$cmd = array_shift($args);
		if($args){
			return static::adapter()->query($cmd,$args[0]);
		}else{
			return static::adapter()->query($cmd);
		}
	}
	
	/*model table temporary replacement
	*tend to fix default
	*/
	public function table($table,$key = "id",$apart_id = NULL){
		if($apart_id){
			$table .= static::_computeTableId($apart_id);
		}
		static::$_defaultMeta = static::$_meta;
		static::$_meta['source'] = $table;
		static::$_meta['key'] = $key;
		return $this;
	}

	public function create($pData){
		if(!$this->_autoValidator($pData)) return false;
		$adapter = static::adapter();
		$adapter->create($pData);
		return $adapter;
	}

	public function escape(){
		return static::adapter()->filter();
	}

	public function find($type,$conditions = array()){
		return static::adapter()->read($type,$conditions);
	}

	public function insert($data,$options = array()){
		return static::adapter()->insert($data,$options);
	}

	public function replace($data,$options = array()){
		return static::adapter()->replace($data,$options);
	}

	public function update($data,$conditions,$options = array()){
		return static::adapter()->update($data,$conditions,$options);
	}

	public function delete($where,$options = array()){
		return static::adapter()->delete($where,$options);
	}

	public function getIncrementId($namespace,$source = 'seq',$options = array()){
		return static::adapter()->autoIncrement($namespace,$source = 'seq',$options = array());
	}

	public function close(){
		static::adapter()->close();
	}

	public function errors($error = NULL){
		if($error) static::adapter()->error = $error;
		return array('errors' => static::adapter()->error);	
	}

	protected function _autoValidator($data){
		if($this->validator){
			$data = array_intersect_key($data, $this->validator);
			if($message = Validator::check($data,$this->validator)){
				static::adapter()->error = $message;
				return false;
			}
		}
		return true;
	}
	
	protected static function _computeTableId($apart_id){
		if($tableDiv = static::$_meta['apart_options']){
			return "_".str_pad($apart_id%$tableDiv['div'],$tableDiv['bit'],0,STR_PAD_LEFT);
		}
		return "";
	}
}

?>
