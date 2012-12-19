<?php
/*
* @yuyang
*/
namespace li3_model\data;

use lithium\util\Validator;

class Model{

	public $validator;

	protected $_resolveSource = null;
	protected $_dbApartOptions;

	protected static $_instances = array();
	protected static $_adapterPool = array();
	protected static $_adapter = 'MySql';
	protected static $_meta  = array(
		'connection' => 'default',
		'source' => __CLASS__,
		'key' => 'id'
	);

	public static function instance(){
		$class = get_called_class();
		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
		}
		return static::$_instances[$class];
	}
     	
	public function sql(){
		$args = func_get_args();
		$cmd = array_shift($args);
		if($args){
			return static::adapter()->query($cmd,$args[0]);
		}else{
			return static::adapter()->query($cmd);
		}
	}

	public function meta($item = NULL){
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
		static::$_adapterPool[$name]->init(static::$_meta);
		return static::$_adapterPool[$name];
	}

	public function create($pData){
		if(!$this->_autoValidator($pData)) return false;
		$adapter = static::adapter();
		$adapter->create($pData);
		$this->resolveTable();
		return $adapter;
	}

	public function find($type,$conditions = array()){
		$return = static::adapter()->read($type,$conditions);
		$this->resolveTable();
		return $return;
	}

	public function insert($data,$options = array()){
		$return = static::adapter()->insert($data,$options);
		$this->resolveTable();
		return $return;
	}

	public function replace($data,$options = array()){
		$return = static::adapter()->replace($data,$options);
		$this->resolveTable();
		return $return;
	}

	public function update($data,$conditions,$options = array()){
		$return = static::adapter()->update($data,$conditions,$options);
		$this->resolveTable();
		return $return;
	}

	public function delete($where,$options = array()){
		$return = static::adapter()->remove($where,$options);
		$this->resolveTable();
		return $return;
	}

	public function getIncrementId($namespace,$source = 'seq',$options = array()){
		return static::adapter()->autoIncrement($namespace,$source = 'seq',$options = array());
	}

	public function close(){
		static::adapter()->close();
	}

	public function db($name){
		static::$_meta['connection'] = $name;
		return $this;
	}

	public function table($table,$key = "id",$cut_id = NULL){
		$pad = '';
		if($tableDiv = $this->_dbApartOptions && $cut_id){
			$pad = "_".str_pad($id%$tableDiv['div'],$tableDiv['bit'],0,STR_PAD_LEFT);
		}
		$source = $table.$pad;
		$this->_resolveSource = array(static::$_meta['source'],static::$_meta['key']);
		static::$_meta['source'] = $source;
		static::$_meta['key'] = $key;
		return $this;
	}

	public function resolveTable(){
		if($this->_resolveSource){
			static::$_meta['source'] = $this->_resolveSource[0];
			static::$_meta['key'] = $this->_resolveSource[1];
			$this->_resolveSource = array();
		}
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
}

?>