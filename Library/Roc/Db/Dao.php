<?php

/**
 * 数据操作类
 *
 * @author Jack Xie <xiejinci@gmail.com>
 * @copyright 2006-2010 1lele.com
 * $Id: Db.php 8 2010-03-18 08:34:29Z xiejc $
 * @history
 */
class Roc_Db_Dao extends Roc_Db_Driver
{

    private $sDbName;

    /**
     * 事务计数
     *
     * @var int
     */
    private $iTransaction = 0;

    /**
     * 存放当前连接
     *
     * @var object
     */
    private $oDbh = null;

    private $bUseCommit = false;

    private $iPingTime = 0;
    //实例
    protected static $_aInstance;
    /**
     * 执行sql数组
     */
    private static $_aSQL = array();

    private static $_iQueryCnt = 0;

    private static $_oDebug = null;

    private static $_aADUSQL = array();

    private static $_iConnentTime = 0;

    private static $_iUseTime = 0;

    /**
     * 构造函数
     *
     * @param array $sDbName
     * @param array $is_static
     * @param bool $bIsPersistent
     * @return void
     */
    public function __construct ($sDbName)
    {
        $this->iPingTime = time();
        $this->sDbName = $sDbName;
        $this->connect();
    }

    public function connect ()
    {
        $iStartTime = microtime(true);
        $this->oDbh = self::getPDO($this->sDbName);
        $iEndTime = microtime(true);
        self::$_iConnentTime = round(($iEndTime - $iStartTime)*1000,2);
    }

    /**
     * 查询操作的底层接口
     *
     * @param string $sql
     *            要执行查询的SQL语句
     * @return Object
     */
    public function execute ($sql)
    {
        $sql = trim($sql);

        $iStartTime = microtime(true);
        self::$_iQueryCnt += 1;
        self::$_aSQL[] = $sql;
        $res = @$this->oDbh->query($sql);
        $iUseTime = round((microtime(true) - $iStartTime) * 1000, 2);
        self::$_iUseTime += $iUseTime;

        // echo $sql . "\n";
        if ($res === false) {
            $sErrInfo = join(' ', $this->oDbh->errorInfo());
            throw new Exception($sErrInfo . ": " . $sql);
            // echo $sql;exit;
        }

        // 影响记录数
        $iAffectedRows = $res->rowCount();

        self::_addLog($sql, $iAffectedRows, $iUseTime, $this->sDbName);

        return $res;
    }

    /**
     * 自动执行操作(针对Insert/Update操作)
     *
     * @param string $sql
     * @return int 影响的行数
     */
    public function query ($sql)
    {
        $res = $this->execute($sql);
        return $res->rowCount();
    }

    /**
     * 根据类型返回结果
     * @param unknown $sql
     * @param unknown $type
     * @return Ambigous <multitype:, multitype:Ambigous <unknown> >|Ambigous <number, NULL>|Ambigous <multitype:, unknown>|Ambigous <multitype:, multitype:unknown >
     */
    public function getByType($type,$aParam,$sAssocField)
    {
        //处理参数
        $sSQL = $this->_buildSQL($aParam);
        $this->$type($sSQL,$sAssocField);
    }
    /**
     * 构建过滤的条件
     *
     * @param array $aParam
     * @return string
     */
    private static function _buildSQL ($aParam)
    {
        $sTable = '';
        $sGroup = '';
        $sWhere = '';
        $aWhere = array();
        if (is_array($aParam)) {
            if (isset($aParam['where']) || isset($aParam['limit']) || isset($aParam['order'])) {
                if (isset($aParam['where']) && is_array($aParam['where'])) {
                    $aWhere = $aParam['where'];
                } elseif (isset($aParam['where'])) {
                    $sWhere = $aParam['where'];
                }

                if (isset($aParam['order'])) {
                    $sOrder = $aParam['order'];
                }
                if (isset($aParam['limit'])) {
                    $sLimit = $aParam['limit'];
                }
                if (isset($aParam['group'])) {
                    $sGroup = $aParam['group'];
                }
                if (isset($aParam['field'])) {
                    $sField = $aParam['field'];
                }
                if (isset($aParam['table'])) {
                    $sTable = $aParam['table'];
                }
            } else {
                $aWhere = $aParam;
            }
        } else {
            $sWhere = $aParam;
        }
        if (! empty($aWhere)) {
            $aTmpWhere = array();
            foreach ($aWhere as $k => $v) {
                if (is_numeric($k) || $k == 'sWhere') {    //兼容,array(0 => 'sField=1', 1 => 'sField>5')这种写法
                    $aTmpWhere[] = $v;
                } else {
                    $aTmpWhere[] = self::_buildField($k, $v);
                }
            }
            $sWhere = join(' AND ', $aTmpWhere);
        }

        if (! empty($sWhere)) {
            $sWhere = 'WHERE ' . $sWhere;
        }

        if (! empty($sOrder)) {
            $sOrder = 'ORDER BY ' . $sOrder;
        }
        if (! empty($sLimit)) {
            $sLimit = 'LIMIT ' . $sLimit;
        }
        if (! empty($sGroup)) {
            $sGroup = 'GROUP BY ' . $sGroup;
        }

        if (empty($sTable)) {
            $sTable = self::getTable();
        }
        $sSQL = "SELECT $sField FROM $sTable $sWhere $sGroup $sOrder $sLimit";
        return $sSQL;
    }

    /**
     * Build一个字段
     * @param unknown $sKey
     * @param unknown $mValue
     * @throws Exception
     */
    public static function _buildField ($sKey, $mValue)
    {
        $sRet = '';
        $aOpt = explode(' ', $sKey);
        $sOpt = strtoupper(isset($aOpt[1]) ? trim($aOpt[1]) : '=');
        $sField = trim($aOpt[0]);
        $sType = $sField[0];
        if (stripos($sField, '.') === false) {
            $sField = '`' . $sField . '`';

        } else {
            $sType = substr($sField, stripos($sField, '.') + 1, 1);
        }

        if (isset(self::$_aOperators[$sOpt])) {
            switch ($sOpt) {
                case '=':
                case '!=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $mVal = $sType == 's' ? "'" . self::quote($mValue) . "'" : $mValue;
                    $sRet = "$sField $sOpt $mVal";
                    break;
                case '+=':
                case '-=':
                case '*=':
                case '/=':
                    $sRet = "$sField = $sField {$sOpt[0]} $mValue";
                    break;
                case 'BETWEEN':
                    if (is_string($mValue)) {
                        $aTmp = explode(',', $mValue);
                    } else {
                        $aTmp = $mValue;
                    }
                    $sRet = "$sField BETWEEN {$aTmp[0]} AND {$aTmp[1]}";
                    break;
                case 'IN':
                case 'NOT':
                    if (is_array($mValue)) {
                        if ($sType == 's') {
                            $mValue = '"' . join('","', $mValue) . '"';
                        } else {
                            $mValue = join(',', $mValue);
                        }
                    }
                    if ($sOpt == 'IN') {
                        $sRet = "$sField IN($mValue)";
                    } else {
                        $sRet = "$sField NOT IN($mValue)";
                    }
                    break;
                case 'LIKE':
                    $sRet = "$sField LIKE '" . self::quote($mValue) . "'";
                    break;
                case 'FIND_IN_SET':
                    $mVal = $sType == 's' ? "'" . self::quote($mValue) . "'" : $mValue;
                    $sRet = "FIND_IN_SET($mVal, $sField)";
                    break;
            }
        } else {
            throw new Exception("Unkown operator $sOpt!!!");
        }

        return $sRet;
    }
    /**
     * 取得所有数据
     *
     * @param string $sql
     *            SQL语句
     * @param string $field
     *            以字段做为数组的key
     * @return array
     */
    public function getAll ($sql, $field = null)
    {
        $res = $this->execute($sql);
        if (empty($res)) {
            return [];
        }
        $rows = $res->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return array();
        }

        if (null != $field) {
            $list = $rows;
            $rows = array();
            foreach ($list as $row) {
                $rows[$row[$field]] = $row;
            }
        }

        return $rows;
    }

    /**
     * 以get_row方式取得所有数据
     *
     * @param string $sql
     *            SQL语句
     * @param int $index
     *            以字段做为数组的key
     * @return array
     */
    public function getAllByRow ($sql, $index = -1)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return array();
        }

        $rows = $res->fetchAll(PDO::FETCH_NUM);
        if (empty($rows)) {
            return array();
        }

        if (-1 != $index) {
            $list = $rows;
            $rows = array();
            foreach ($list as $row) {
                $rows[$row[$index]] = $row;
            }
        }

        return $rows;
    }

    /**
     * 取得指定条数的数据
     *
     * @param string $sql
     *            SQL语句
     * @param int $offset
     *            LIMIT的第一个参数
     * @param int $limit
     *            LIMIT的第二个参数
     * @param string $field
     *            以字段做为数组的key
     * @return array
     */
    public function getLimit ($sql, $offset, $limit, $field = null)
    {
        $limit = intval($limit);
        if ($limit <= 0) {
            return array();
        }
        $offset = intval($offset);
        if ($offset < 0) {
            return array();
        }
        $sql = $sql . ' LIMIT ' . $limit;
        if ($offset > 0) {
            $sql .= ' OFFSET ' . $offset;
        }
        return $this->getAll($sql, $field);
    }

    /**
     * 返回所有记录中以第一个字段为值的数组
     *
     * @param string $sql
     *            SQL语句
     * @param bool $isMaster
     *            主从
     * @return array
     */
    public function getCol ($sql)
    {
        $list = $this->getAllByRow($sql);
        $rows = array();
        foreach ($list as $row) {
           $rows[] = $row[0];
        }

        return $rows;
    }

    /**
     * 返回所有记录中以第一个字段为key,第二个字段为值的数组
     *
     * @param string $sql
     *            SQL语句
     * @return array
     */
    public function getPair ($sql)
    {
        $list = $this->getAllByRow($sql);
        $rows = array();
        foreach ($list as $row) {
           $rows[$row[0]] = $row[1];
        }

        return $rows;
    }

    /**
     * 取得第一条记录
     *
     * @param string $sql
     *            SQL语句
     * @return array
     */
    public function getRow ($sql)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return array();
        }

        $row = $res->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            return array();
        }

        return $row;
    }

    /**
     * 取得第一条记录的第一个字段值
     *
     * @param string $sql
     *            SQL语句
     * @return int string
     */
    public function getOne ($sql)
    {
        $res = $this->execute($sql);
        if (! $res) {
            return null;
        }

        return $res->fetchColumn(0);
    } 

    /**
     * 替换操作
     * @param unknown $table
     * @param unknown $row
     * @param string $quote
     * @return number
     */
    public function replace ($table, $row, $quote = false)
    {
        return $this->insert($table, $row, $quote, 'REPLACE');
    }

    /**
     * 插入一条记录
     *
     * @param string $table 表数
     * @param array $row 数据
     * @param bool $quote 是否进行数据过滤
     * @return int 影响的条数
     */
    public function insert ($table, $row, $quote = false, $type = 'INSERT')
    {
        $cols = array();
        $vals = array();
        if (false == $quote) {
            foreach ($row as $col => $val) {
                $cols[] = $col;
                $vals[] = $val;
            }
        } else {
            foreach ($row as $col => $val) {
                $cols[] = $col;
                $vals[] = $this->quote($val);
            }
        }
        $sql = $type . ' INTO `' . $table . '`' . '(`' . join('`, `', $cols) . '`) ' . 'VALUES (\'' . join('\',\'', $vals) . '\')';
        return $this->query($sql);
    }

    /**
     * 插入一条记录
     *
     * @param string $table 表数
     * @param array $row 数据
     * @param bool $quote 是否进行数据过滤
     * @return int 影响的条数
     */
    public function _insert ($table, $row, $quote = false, $type = 'INSERT')
    {
        $cols = array();
        $vals = array();
        if (false == $quote) {
            foreach ($row as $col => $val) {
                $cols[] = $col;
                $vals[] = $val;
            }
        } else {
            foreach ($row as $col => $val) {
                $cols[] = $col;
                $vals[] = $this->quote($val);
            }
        }
        $sql = $type . ' INTO `' . $table . '`' . '(`' . join('`, `', $cols) . '`) ' . 'VALUES (\'' . join('\',\'', $vals) . '\')';
        $rowCount = $this->query($sql);
        return $rowCount? $this->lastInsertId(): 0;
    }

    /**
     * 插入一批数据库
     *
     * @param string $table
     *            表名
     * @param array $rows
     *            数据列表 array( array( 'field1'=>$val1, 'field2'=>$val2, ... ), array( 'field1'=>$val1, 'field2'=>$val2, ...), ... )
     * @param string $type
     *            插入类型(INSERT|REPLACE)
     * @param bool $quote
     *            是否进行数据过滤
     * @param bool $return_sql
     *            如果启用，则无数据库操作，仅返回SQL字符串。
     * @return int 影响的条数
     */
    public function insertRows ($table, $rows, $type = 'INSERT', $quote = false)
    {
        if (empty($rows)) {
            return true;
        }


        $n = 0;
        $cols = array();
        $vals = array();
        foreach ($rows as $row) {
            $arr = array();
            foreach ($row as $col => $val) {
                if ($n == 0) {
                    $cols[] = $col;
                }
                if ($quote) {
                    $val = $this->quote($val);
                }
                $arr[] = $val;
            }
            $vals[] = '(\'' . join('\', \'', $arr) . '\')';
            $n++;
        }
        $sql = $type . ' INTO `' . $table . '`(`' . join('`,`', $cols) . '`) VALUES' . join(',', $vals);
        return $this->query($sql);
    }

    /**
     * 数据更新
     *
     * @param string $table
     *            表名
     * @param array $data
     *            记录
     * @param string $where
     *            更新条件
     * @param bool $quote
     *            是否进行过滤
     * @return int 影响的条数
     */
    public function update ($table, $data, $where = '', $quote = false)
    {
        $sets = array();
        foreach ($data as $col => $val) {
            if ($quote) {
                $val = $this->quote($val);
            }

            // 配制+=,-=,/=,*=的情况
            if (preg_match('/^(.+)([\+\-\/\*])=$/', $col, $tmp)) {
                $col = trim($tmp[1]);
                $opt = trim($tmp[2]);
                $sets[] = '`' . $col . '` = `' . $col . '`' . $opt . ' ' . $val;
            } else {
                $sets[] = '`' . $col . '` = \'' . $val . '\'';
            }
        }

        $sql = 'UPDATE `' . $table . '`' . ' SET ' . implode(', ', $sets) . (($where) ? ' WHERE ' . $where : '');
        return $this->query($sql);
    }

    /**
     * 删除数据
     *
     * @param string $table
     *            表名
     * @param string $where
     *            条件
     * @return int
     */
    public function delete ($table, $where)
    {
        return $this->query('DELETE FROM ' . $table . ' WHERE ' . $where);
    }

    /**
     * 取得最后的lastInsertId
     *
     * @return int
     */
    public function lastInsertId ()
    {
        return $this->oDbh->lastInsertId();
    }

    /**
     * 数据过滤
     *
     * @param mixed $value
     *            要过滤的值
     * @return string
     */
    public function quote ($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return @addcslashes($value, "\000\n\r\\'\"\032");
    }

    /**
     * 事务开始
     *
     * @param bool $no_check
     * @return bool
     */
    public function begin ()
    {
        if ($this->iTransaction == 0) {
            if ($this->bUseCommit) {
                throw new Exception('本次操作里已经使用了一次事务。', 3);
            }
            beginTransaction()
            $this->bUseCommit = true;
        }
        $this->iTransaction ++;
        return true;
    }

    /**
     * 事务提交
     */
    public function commit ()
    {
        if ($this->iTransaction < 1) {
            throw new Exception('出错啦！事务不配对！', 3);
        }
        $this->iTransaction --;
        if (0 == $this->iTransaction) {
            $this->execute('COMMIT');
        }
        return true;
    }

    /**
     * 事务回滚
     */
    public function rollBack ()
    {
        $this->execute('ROLLBACK');
        $this->iTransaction = 0;
        $this->bUseCommit = false;
        return true;
    }

    /**
     * 设置Debug对象
     * @param unknown $p_mDebug
     */
    public static function setDebug ($p_mDebug)
    {
        self::$_oDebug = $p_mDebug;
    }

    /**
     * 取得Debug统计信息
     * @return string
     */
    public static function getDebugStat ()
    {
        return '[MySQL]->Query: ' . self::$_iQueryCnt . ', Connent Time: ' . self::$_iConnentTime . ' Use Time:' . self::$_iUseTime;
    }

    /**
     *
     * @param unknown $sSQL
     */
    private static function _addLog ($sSQL, $iAffectedRows, $iUseTime, $sDbName)
    {
        if (Util_Tools::getRunMethod() != 'CLI') {
            if (count(self::$_aSQL) > 300) {
                self::$_aSQL = array();
            }
            self::$_aSQL[] = $sSQL;
            
            // 记录Debug日志
            if (self::$_oDebug) {
                self::$_oDebug->debug('[DB->' . $sDbName . ']: ' . $sSQL . ' AffectedRows:' . $iAffectedRows . ' Use Time:' . $iUseTime . '毫秒');
            }
    
            // 记录增删改日志
            $bIsADU = strtolower(substr($sSQL, 0, 6)) == 'select' ? false : true;
            if ($bIsADU) {
                self::_addADUSQL('[DB->' . $sDbName . ']: ' . $sSQL . ' AffectedRows:' . $iAffectedRows . ' Use Time:' . $iUseTime . '毫秒');
            }
        }
    }
    
    /**
     * 记录ADU日志
     * @param unknown $sSQL
     */
    private static function _addADUSQL ($sSQL)
    {
        if (count(self::$_aADUSQL) > 300) {
            self::$_aADUSQL = array();
        }

        self::$_aADUSQL[] = $sSQL;
    }

    /**
     * 取得最后执行的一条SQL
     * @return string
     */
    public static function getLastSQL()
    {
        if (self::$_aSQL) {
            return self::$_aSQL[count(self::$_aSQL)-1];
        }

        return '';
    }

    public function __destruct ()
    {
        $this->close();
    }
    /*
     * 关闭一次操作只允许一次事务
     */
    public function closeTranction (){
        $this->bUseCommit = false;
    }

    //获取实例
    public static function getPDO($sDbName){
        //是有已连接过
        if (!isset(self::$_aInstance[$sDbName])) {
            $aConf = Roc_G::getConf($sDbName, 'Database');
            $aOption = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ];
            self::$_aInstance[$sDbName] = new PDO($aConf['dsn'], $aConf['user'], $aConf['passwd'], $aOption);
        }

        return self::$_aInstance[$sDbName];
    }
    /*
     * 获取自身的实例
     */
    public static function getInstance($sDbName){
        static $_aDbInstance = [];
        if (!isset($_aDbInstance[$sDbName])) {
            $_aDbInstance[$sDbName] = new self($sDbName);
        }
        return $_aDbInstance[$sDbName];
    }
}