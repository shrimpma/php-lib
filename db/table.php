<?php
/**
* 基于数据表,操作基本类
*/
	 
abstract class table {
    protected $db;
	protected $dbRead;
    protected $hash;
	protected $table;
	protected $fields;
	protected $dbKey ='db';

    final public function __get($name){
        if (!property_exists($this, $name)) {
            return NULL;
        }
        else if (!is_null($this->$name)){
		if ($name == 'db') {
			$this->db->table($this->table,$this->fields);
		}
            return $this->$name;
        }
        $funName = 'get'. ucfirst($name);
        if (method_exists($this, $funName)){
            $this->$funName();
        }
        return $this->$name;
	}
	protected function getDb(){
		$db = pool::db($this->dbKey,$this->hash);
		$db->table($this->table,$this->fields);
		return $this->db = $db;
	}

	protected function getDbRead(){
	
	}

	public function setTableName($strTableName)
	{
		$this->table = $strTableName;
		if (is_object($this->db)) {
			$this->db->table = $strTableName;
		}
	}
 }