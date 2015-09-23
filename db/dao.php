<?php
/**
 * Dao基础类
 */
class Dao
{
    const ORDER_ASC = 1;
    const ORDER_DESC = 2;
    const BELONGS_TO = 'belongsTo';
    
    //子类需覆盖的属性
    protected $dbName = 'db'; //数据库标识
    protected $tableBaseName = ''; //表名
    protected $fields = array(); //表字段
    protected $validCondFields = array(); //可以作为where条件的表字段
    protected $primaryKey = 'id'; //主键
    protected $orderByField = 'id'; //用于排序的表字段
    protected $splitTableNum = 0; //分表数量

    //内部属性
    private $objTable;
    private $condition = array();
    private $logicalOperator = ' AND ';
    private $conditionClause = array();
    private $with = array();
    public $sql; //最近执行的sql, 只读属性

    public function __construct()
    {
        $this->objTable = new Base_Table(0, $this->dbName);
        $this->objTable->tableBaseName = $this->tableBaseName;
        $this->objTable->splitTableNum = $this->splitTableNum;
        $this->objTable->fields = $this->fields;
        $this->objTable->validCondFields = $this->validCondFields;
        $this->objTable->orderByField = $this->orderByField;
        $this->setTableNameModifier('');
    }
    
    /**
     * 设置排序字段
     * 
     * @param string $field 待排序的字段
     */
    protected function setOrdField($field){
        $this->objTable->orderByField = $field;
    }

    /**
     * 得到表名
     */
    public function getTableName()
    {
        return $this->objTable->db->table;
    }

    /**
     * 为表名添加后缀
     */
    protected function setTableNameModifier($str)
    {
        $this->objTable->setTableName($this->objTable->tableBaseName.$str);
    }

    /**
     * 重设条件
     */
    public function resetCondition()
    {
        $this->condition = array();
    }

    /**
     * 重设条件子句
     */
    public function resetConditionClause()
    {
        $this->conditionClause = array();
    }

    /**
     * 设置条件
     */
    public function setCondition($strField, $value, $op='=')
    {
        if (!in_array($strField, $this->objTable->validCondFields)) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        if (is_array($value) && $value) {
            $one = pos($value);
            if (is_int($one) || is_float($one)) {
                $inStr = implode(',', $value);
            } else {
                $inStr = '';
                foreach ($value as $one) {
                    $inStr .= $this->objTable->db->quote($one).',';
                }
                $inStr = substr($inStr, 0, -1);
            }
            if ($op == '=' || strtolower($op) == 'in') {
                $this->condition[] = "`$strField` IN($inStr)";
            } else {
                $this->condition[] = "`$strField` $op($inStr)";
            }
        } elseif (is_int($value)) {
            $this->condition[] = "`$strField` $op $value";
        } elseif (is_string($value) && $op === null) {
            $this->condition[] = "`$strField` $value";
        } elseif (is_string($value)) {
            $this->condition[] = "`$strField` $op ".$this->objTable->db->quote($value);
        } elseif ($value === null) {
            $this->condition[] = "`$strField` $op $value";
        }
    }

    /**
     * 设置条件组
     */
    public function setConditions(array $arrConditions)
    {
        foreach ($arrConditions as $one) {
            if (!is_array($one) || count($one)<2) {
                continue;
            }
            $this->setCondition($one[0], $one[1], array_key_exists(2,$one)?$one[2]:'=');
        }
    }

    /**
     * 设置用于连接条件的逻辑操作符
     */
    public function setConditionLogicalOperator($operator)
    {
        if ($operator) {
            $this->logicalOperator = " $operator ";
            return true;
        } else {
            return false;
        }
    }

    /**
     * 将当前的条件数组拼合成sql
     */
    public function buildConditionClause()
    {
        $clause = $this->getConditionStr(false);
        if ($clause) {
            $this->conditionClause[] = "($clause)";
        }
        return true;
    }

    /**
     * 得到条件串
     * @param bool $containClause Description
     */
    private function getConditionStr($containClause=true)
    {
        if ($containClause && $this->conditionClause) {
            $clauseSql = implode($this->logicalOperator, $this->conditionClause);
        } else {
            $clauseSql = '';
        }
        if ($this->condition) {
            $condSql = implode($this->logicalOperator, $this->condition);
        } else {
            $condSql = '';
        }
        return $clauseSql && $condSql ? $clauseSql.' '.$this->logicalOperator.' '.$condSql : ($condSql?$condSql:$clauseSql);
    }

    /**
     * 插入一行记录
     * @param arr $params 以字段名为键，字段值为值得数组
     * @return int 新记录id
     */
    public function insert(array $params)
    {
        if (!$params) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->objTable->filterFields($params);
        if (!$params) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->objTable->db->reset();
        $intId = $this->objTable->db->insert($params);
        $this->sql = $this->objTable->db->sql;
        if ($intId === false) {
            throw new ExceptionBase(ExceptionCodes::DB_INSERT_ERROR, $this->objTable->db->msg);
        }
        return $intId;
    }

    /**
     * 插入一行记录，当出现key重复时更新记录
     * @param arr $insertData 插入的数据
     * @param arr $updateData 更新的数据
     * @return int 新记录id
     */
    public function insertDuplicate(array $insertData, array $updateData)
    {
        if (!$insertData || !$updateData) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->objTable->filterFields($insertData);
        $this->objTable->filterFields($updateData);
        if (!$insertData || !$updateData) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->objTable->db->reset();
        $intId = $this->objTable->db->insertDuplicate($insertData, $updateData);
        $this->sql = $this->objTable->db->sql;
        if ($intId === false) {
            throw new ExceptionBase(ExceptionCodes::DB_INSERT_ERROR, $this->objTable->db->msg);
        }
        return $intId;
    }

    /**
     * 取一行记录
     * 需要提前用setCondition(s)设置where条件
     * @param arr $fields select的字段列表
     * @return arr 结果数组,键为字段名
     */
    public function findOne($fields=array())
    {
        if (!$fields) {
            $fields = $this->fields;
        } elseif (is_array($fields)) {
            $this->objTable->filterFields($fields, false);
            if (!$fields) {
                throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
            }
        }
        $this->objTable->db->reset();
        $this->objTable->db->select($fields);
        $strConds = $this->getConditionStr();
        if ($strConds) {
            $this->objTable->db->where($strConds);
        }
        $arrResult = $this->objTable->db->find(2);
        $this->sql = $this->objTable->db->sql;
        if ($arrResult === false) {
            throw new ExceptionBase(ExceptionCodes::DB_SELECT_ERROR, $this->objTable->db->msg);
        }
        return $this->processWith($arrResult, 1);
    }

    /**
     * 取多行记录
     * 需要提前用setCondition(s)设置where条件
     * @param arr $fields select的字段列表
     * @param int $offset 记录起始位置
     * @param int $limit  记录数
     * @param const $orderBy 记录排序类型(ORDER_ASC/ORDER_DESC),按$this->orderByField排序
     * @param str $groupBy GROUP BY子句(不含GROUP BY)
     * @return 结果集,每个数组元素为一行
     */
    public function findAll($fields=array(), $offset=0, $limit=0, $orderBy='', $groupBy='')
    {
        if (!$fields) {
            $fields = $this->fields;
        } elseif (is_array($fields)) {
            $this->objTable->filterFields($fields, false);
            if (!$fields) {
                throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
            }
        }
        $this->objTable->db->reset();
        $this->objTable->db->select($fields);
        $strConds = $this->getConditionStr();
        if ($strConds) {
            $this->objTable->db->where($strConds);
        }
        if ($orderBy === self::ORDER_ASC || $orderBy === self::ORDER_DESC) {
            $orderBy = $this->objTable->getOrderByStr($orderBy);
        }
        $arrResult = $this->objTable->db->findAll($offset, $limit, $orderBy, $groupBy);
        $this->sql = $this->objTable->db->sql;

        if ($arrResult === false) {
            throw new ExceptionBase(ExceptionCodes::DB_SELECT_ERROR, $this->objTable->db->msg);
        }
        return $this->processWith($arrResult);
    }

    /**
     * 更新记录
     * 需要提前用setCondition(s)设置where条件
     * @param arr $arrData 需要更新的数据(键为字段名,值为要更新到的值)
     * @return int 返回受影响的行数/出错返回false
     */
    public function update(array $arrData)
    {
        if (!$arrData) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->objTable->db->reset();
        $strConds = $this->getConditionStr();
        if ($strConds) {
            $this->objTable->db->where($strConds);
        }
        $intRows = $this->objTable->db->update($arrData);
        $this->sql = $this->objTable->db->sql;
        if ($intRows === false) {
            throw new ExceptionBase(ExceptionCodes::DB_UPDATE_ERROR, $this->objTable->db->msg);
        }
        return $intRows;
    }

    /**
     * 删除记录
     * 需要提前用setCondition(s)设置where条件
     * @return int 返回受影响的行数/出错返回false
     */
    public function delete()
    {
        $this->objTable->db->reset();
        $strConds = $this->getConditionStr();
        if ($strConds) {
            $this->objTable->db->where($strConds);
        }
        $intRows = $this->objTable->db->delete();
        $this->sql = $this->objTable->db->sql;
        if ($intRows === false) {
            throw new ExceptionBase(ExceptionCodes::DB_DELETE_ERROR, $this->objTable->db->msg);
        }
        return $intRows;
    }

    /**
     * 记录数
     * 需要提前用setCondition(s)设置where条件
     * @param str $strCountSql count语句,默认count(*)
     * @return int 返回记录数
     */
    public function count($strCountSql='count(*)')
    {
        $this->objTable->db->reset();
        $strConds = $this->getConditionStr();
        if ($strConds) {
            $this->objTable->db->where($strConds);
        }
        $intRows = $this->objTable->db->count($strCountSql);
        $this->sql = $this->objTable->db->sql;
        if ($intRows === false) {
            throw new ExceptionBase(ExceptionCodes::DB_DELETE_ERROR, $this->objTable->db->msg);
        }
        return $intRows;
    }

    /**
     * 按id获得记录
     * @param arr/int $ids id数组
     * @param str $strIdName 唯一标识id字段的名字(默认为id)
     * @return arr 以id为键的数组
     */
    public function getByIds($ids, $strIdName='')
    {
        if (is_numeric($ids)) {
            $ids = array($ids);
        }
        if (!$strIdName) {
            $strIdName = $this->primaryKey;
        }
        $this->objTable->db->reset();
        $this->objTable->db->where(sprintf("$strIdName IN(%s)", implode(',', $ids)));
        $rows = $this->objTable->db->findAll();
        $this->sql = $this->objTable->db->sql;
        if (!$rows || !is_array($rows)) {
            return array();
        }
        $result = array();
        foreach ($rows as $row) {
            $result[$row[$strIdName]] = $row;
        }
        return $this->processWith($result);
    }

    /**
    * 按id获得记录
    * @param arr/int $ids id数组
    * @param str $strIdName 唯一标识id字段的名字(默认为id)
    * @return arr 以id为键的数组
    */
    public function getListByIds($ids, $strIdName='')
    {
        if (is_numeric($ids)) {
            $ids = array($ids);
        }
        if (!$strIdName) {
            $strIdName = $this->primaryKey;
        }
        $this->objTable->db->reset();
        $this->objTable->db->where(sprintf("$strIdName IN(%s)", implode(',', $ids)));
        $rows = $this->objTable->db->findAll();
        $this->sql = $this->objTable->db->sql;
        if (!$rows || !is_array($rows)) {
            return array();
        }
        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }
        return $this->processWith($result);
    }

    /**
     * 通过条件获取数量
     * @param arr $arrConds 条件数组
     * @return int 数量
     */
    public function getCountByConds(array $arrConds)
    {
        if (!$arrConds) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->resetCondition();
        $this->setConditions($arrConds);
        return $this->count();
    }

    /**
     * 通过条件获取数据列表
     * @param arr $arrConds 条件数组
     * @param int $offset
     * @param int $limit
     * @return arr 数据列表
     */
    public function getListByConds(array $arrConds, $offset=0, $limit=10)
    {
        if (!$arrConds) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        $this->resetCondition();
        $this->setConditions($arrConds);
        $row = $this->findAll(array(), $offset, $limit);
        if (!$row || !is_array($row)) {
            return array();
        }
        return $row;
    }

    /**
     * 按id逻辑删除
     * @param arr/int $ids id数组
     * @param int $status 删除标记值
     * @param str $strIdName 唯一标识id字段的名字(默认为id)
     * @param str $strStatusName 状态字段的名字(默认为status)
     * @return int 影响的行数
     */
    public function deleteByIds($ids, $status, $strIdName='', $strStatusName='status')
    {
        return $this->updateByIds($ids, array($strStatusName=>$status), $strIdName);
    }

    /**
     * 按id更新记录
     * @param arr/int $ids id数组
     * @param arr $arrData 需要更新的数据(键为字段名,值为要更新到的值)
     * @param str $strIdName 唯一标识id字段的名字(默认为id)
     * @return int 影响的行数
     */
    public function updateByIds($ids, $arrData, $strIdName='')
    {
        if ((!is_numeric($ids) && !is_array($ids)) || !is_array($arrData)) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }
        if (is_numeric($ids)) {
            $ids = array($ids);
        }
        if (!$strIdName) {
            $strIdName = $this->primaryKey;
        }
        $this->objTable->db->reset();
        $strConds = $this->getConditionStr();
        if ($strConds) {
            $this->objTable->db->where($strConds);
        }
        $this->objTable->db->where(sprintf("$strIdName IN(%s)", implode(',', $ids)));
        $intRows = $this->objTable->db->update($arrData);
        $this->sql = $this->objTable->db->sql;
        return $intRows;
    }

    /**
     * 按条件更新记录
     * @param arr $arrConds 条件数组
     * @param arr $arrData 需要更新的数据(键为字段名,值为要更新到的值)
     * @return int 影响的行数
     */
    public function updateByConds($arrConds, $arrData)
    {
        $this->resetCondition();
        $this->setConditions($arrConds);
        return $this->update($arrData);
    }

    public function parseToWhere($condition)
    {
        if (empty($condition)) {
            return true;
        }
        settype($condition, 'array');
        foreach ($condition as $k => $v) {
            $strField = trim($k);
            $opt = '=';
            if (!ctype_graph($k)) {
                $kArr = preg_split('/\s+/', $k);
                if (count($kArr) > 1) {
                    $strField = $kArr[0];
                    $opt = strtolower($kArr[1]);
                    $aim = $kArr[2];
                    $compare = $kArr[3];
                }
            }
            if (!in_array($strField, $this->objTable->validCondFields)) {
                continue;
            }
            $value = '';
            if (is_array($v) && $v) {
                $opt = 'in';
                $inStr = '';
                foreach ($v as $one) {
                    $inStr .= $this->objTable->db->quote($one).',';
                }
                $inStr = substr($inStr, 0, -1);
                $value = "($inStr)";
            } elseif (is_int($v)) {
                $value = $v;
            } elseif (is_string($v)) {
                $value = $this->objTable->db->quote($v);
            } elseif ($v === null) {
                $value = "$v";
            }
            if (in_array($opt, array('&', '|'))) {
                if (!is_numeric($aim)) {
                    continue;
                }
                if (empty($compare)) {
                    $compare = '=';
                }
                $this->condition[] = "`$strField` $opt $aim $compare $value";
            } elseif (in_array($opt, array('=', '>=', '<=', '>', '<', '!=', 'in', 'like'))) {
                $this->condition[] = "`$strField` $opt $value";
            }
        }
    }

    /**
     * 启动一个事务
     * @return boolean
     */
    public function beginTrans()
    {
        try {
            if (!($this->objTable->db->inTransaction())) {
                return $this->objTable->db->beginTransaction();
            }
            return true;
        } catch (Exception $e) {
            $errMsg = array(
                'msg' => $e->getMessage(),
                'sql' => $this->objTable->db->sql,
            );
            throw new ExceptionBase(ExceptionCodes::DB_TRANSACTION_ERROR, $errMsg);
        }
    }

    /**
     * 提交一个事务
     * @return boolean
     */
    public function commitTrans()
    {
        try {
            if ($this->objTable->db->inTransaction()) {
                return $this->objTable->db->commit();
            }
            return false;
        } catch (Exception $e) {
            $errMsg = array(
                'msg' => $e->getMessage(),
                'sql' => $this->objTable->db->sql,
            );
            throw new ExceptionBase(ExceptionCodes::DB_COMMIT_ERROR, $errMsg);
        }
    }

    /**
     * 回滚一个事务
     * @return boolean
     */
    public function rollBackTrans()
    {
        try {
            if ($this->objTable->db->inTransaction()) {
                return $this->objTable->db->rollBack();
            }
            return false;
        } catch (Exception $e) {
            $errMsg = array(
                'msg' => $e->getMessage(),
                'sql' => $this->objTable->db->sql,
            );
            throw new ExceptionBase(ExceptionCodes::DB_ROLLBACK_ERROR, $errMsg);
        }
    }

    public function getTableStatus()
    {
        return $this->objTable->db->row("SHOW TABLE STATUS LIKE '{$this->objTable->table}'", PDO::FETCH_ASSOC, 2);
    }

    /**
     * 定义关联关系
     * @return array
     */
    public function relations()
    {
       return array();
    }

    /**
     * 使用关联查询
     * @return $this
     */
    public function with()
    {
        if (func_num_args() > 0) {
            $with = func_get_args();
            if (!empty($with)) {
                $this->with = array_merge($this->with, $with);
            }
        }
        return $this;
    }

    /**
     * 处理关联关系
     * @param $result 要处理的结果数组
     * @param int $type 1表示一维数组 2表示二维数组
     * @return mixed
     * @throws ExceptionBase
     */
    public function processWith($result, $type = 2)
    {
        if (empty($result) || empty($this->with)) {
            $this->resetWith();
            return $result;
        }
        $relations = $this->relations();
        foreach ($this->with as $withName) {
            if (!array_key_exists($withName, $relations)) {
                $this->resetWith();
                throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
            }
            $with = $relations[$withName];
            if (!isset($with[0], $with[1], $with[2])) {
                $this->resetWith();
                throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
            }
            // 获取关联字段
            $dao = new $with[1];
            if (is_array($with[2])) {
                $selfFiled = array_shift(array_keys($with[2]));
                $withFiled = array_shift($with[2]);
            } else {
                $selfFiled = $with[2];
                $withFiled = $with[2];
            }
            if ($with[0] === self::BELONGS_TO) {
                $tmpResult = array();
                // 取关联数据
                if ($type == 1) {
                    if ($result[$selfFiled]) {
                        $dao->setCondition($withFiled, $result[$selfFiled]);
                        $tmpResult = $dao->findOne();
                    }
                } elseif ($type == 2) {
                    $arrResult = Utils_Array::getFieldData($result, $selfFiled);
                    if ($arrResult) {
                        $arrResult = array_unique($arrResult);
                        $dao->setCondition($withFiled, $arrResult);
                        $withResult = $dao->findAll();
                        $tmpResult = Utils_Array::getKeyData($withResult, $withFiled);
                    }
                }

                // 将关联数据合并至原有结果数组
                foreach ($result as &$val) {
                    $withKey = $withName;
                    $withVal = array();
                    if (array_key_exists($withKey, $val)) {
                        $withKey = $withName . 'With';
                    }
                    if ($type == 1) {
                        $withVal = $tmpResult;
                    } elseif ($type == 2 && array_key_exists($val[$selfFiled], $tmpResult)) {
                        $withVal = $tmpResult[$val[$selfFiled]];
                    }
                    $val[$withKey] = $withVal;
                }
            }
        }
        $this->resetWith();
        return $result;
    }

    /**
     * 重置关联
     */
    public function resetWith()
    {
        $this->with = array();
    }

    public function getErrorInfo()
    {
        return $this->objTable->db->msg;
    }

    /**
     * 直接执行sql
     * @param $sql
     * @return mixed
     * @throws ExceptionBase
     */
    public function exec($sql)
    {
        if (empty($sql)) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }

        $this->objTable->db->reset();
        $res = $this->objTable->db->exec($sql);

        if ($res === false) {
            throw new ExceptionBase(ExceptionCodes::DB_QUERY_ERROR, $this->objTable->db->errorInfo());
        }
        return $res;
    }

    public function execute($sql, $param = array())
    {
        if (empty($sql)) {
            throw new ExceptionBase(ExceptionCodes::PARAM_ERROR);
        }

        $this->objTable->db->reset();
        $res = $this->objTable->db->execute($sql, $param);

        if ($res === false) {
            throw new ExceptionBase(ExceptionCodes::DB_QUERY_ERROR, $this->objTable->db->msg);
        }
        return $res;
    }

    public function parseToOrder($condition = array())
    {
        $orderBy = '';
        if (empty($condition) || !is_array($condition)) {
            return $orderBy;
        }

        foreach ($condition as $strField => $val) {
            if (!in_array($strField, $this->objTable->fields)) {
                continue;
            }
            $val = strtolower($val);
            if ($val === 'asc') {
                $orderBy .= " {$strField} ASC,";
            } elseif ($val === 'desc') {
                $orderBy .= " {$strField} DESC,";
            } else {
                $orderBy .= '';
            }
        }
        $orderBy = substr($orderBy, 0, -1);
        return empty($orderBy) ? '' : $orderBy;
    }
}

class Base_Table extends table
{
    protected $tableBaseName = '';
    protected $splitTableNum = 0;
    protected $fields = array();
    protected $validCondFields = array();
    protected $orderByField = 'id';

    public function __construct($hash=0, $dbName='db')
    {
        $this->hash = isset($hash) ? $hash : 0;
        $this->dbKey = $dbName;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function filterFields(array &$arrParam, $isFilterKeys=true)
    {
        $arrNewParam = array();
        foreach ($arrParam as $k => $v) {
            if ($isFilterKeys) {
                if (in_array($k, $this->fields)) {
                    $arrNewParam[$k] = $v;
                }
            } else {
                if (in_array($v, $this->fields)) {
                    $arrNewParam[] = $v;
                }
            }
        }
        $arrParam = $arrNewParam;
    }

    public function getOrderByStr($intOrderBy)
    {
        switch ($intOrderBy) {
            case Base_Dao::ORDER_DESC:
                $orderBy = $this->orderByField.' DESC';
                break;
            case Base_Dao::ORDER_ASC:
                $orderBy = $this->orderByField.' ASC';
                break;
            default :
                $orderBy = '';
        }
        return $orderBy;
    }
}
