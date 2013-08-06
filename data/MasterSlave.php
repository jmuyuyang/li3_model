<?php
/*
* @yuyang
*/
namespace li3_model\data;

use lithium\util\Validator;

/*master slave model only support mysql adapter
*/
class MasterSlave{

	public $validator;
	protected $_master;
	protected $_slave;
	protected $_mysql;

	protected static $_instances = array();
	protected static $_defaultMeta = array();
	protected static $_meta  = array(
		'source' => __CLASS__,
		'key' => 'id',
		'apart_options' => array()
	);

	protected static $master_slave = array();

	public static function instance(){
		$class = get_called_class();
		if (!isset(static::$_instances[$class])) {
			static::$_instances[$class] = new $class();
		}
		return static::$_instances[$class];
	}

	public static function config($config){
		$keys = array("master","slave");
		foreach($keys as $key){
			if(isset($config[$key])) self::$master_slave[$key] = $config[$key];
		}
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

	public static function adapter($name){
		$adapter = "li3_model\data\db\MySql\Adapter";
		$instance = new $adapter($name,true);
		if(!$instance->_db) return false;
		$instance->init(static::$_meta);
		return $instance;
	}

	public function master(){
		if(isset(self::$master_slave['master'])){
			if(!$this->_master) {
				$instance = static::adapter(self::$master_slave['master']);
				if(!$instance) return false;
				$this->_master = $instance;
			}
			$this->_mysql = $this->_master;
			$this->fixDefault();
			return $this->_mysql;
		}
		return false;
	}

	public function slave(){
		if(isset(static::$master_slave['slave']) && is_array(self::$master_slave['slave'])){
			if(!$this->_slave){
				$slave = array_rand(self::$master_slave['slave']);
				$adapter = static::adapter($slave);
				if(!$adapter){
					$slave_arr = static::$master_slave['slave'];
					while(!$adapter){
						unset($slave_arr[$slave]);
						if(!$slave_arr) break;
						$slave = array_rand($slave_arr);
						$adapter = static::adapter($slave);
					}
				}
				if($adapter) $this->_slave = $adapter;
			}
			if($this->_slave) {
				$this->_mysql = $this->_slave;
				$this->fixDefault();
				return $this->_mysql;
			}
		}
		return $this->master();
	}

	public function fixDefault(){
		if(static::$_defaultMeta){
			$this->_mysql->init(static::$_meta);
			static::$_meta = static::$_defaultMeta;
			static::$_defaultMeta = array();
		}
	}
     	
	public function query($sql,$args = array()){
		$tags = explode(' ', $sql, 2);
        switch (strtoupper($tags[0])) {
            case 'SELECT':
                return $this->slave()->query($sql, $args);
            default:
                return $this->master()->query($sql, $args);
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
		$adapter = $this->master();
		$adapter->create($pData);
		return $adapter;
	}

	public function escape($current = "master"){
		if($current == "master") return $this->master()->filter();
		if($current == "slave") return $this->slave()->filter();
	}

	public function find($type,$conditions = array()){
		return $this->slave()->read($type,$conditions);
	}

	public function insert($data,$options = array()){
		return $this->master()->insert($data,$options);
	}

	public function replace($data,$options = array()){
		return $this->master()->replace($data,$options);
	}

	public function update($data,$conditions,$options = array()){
		return $this->master()->update($data,$conditions,$options);
	}

	public function delete($where,$options = array()){
		return $this->master()->delete($where,$options);
	}

	public function close(){
		if($this->_master) $this->_master->close();
		if($this->_slave) $this->_slave->close();
	}

	public function error($error = null){
		if($error) $this->_mysql->error->$error;
		else return $this->_mysql->error;
	}

	protected function _autoValidator($data){
		if($this->validator){
			$data = array_intersect_key($data, $this->validator);
			if($message = Validator::check($data,$this->validator)){
				$this->master()->error = $message;
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
