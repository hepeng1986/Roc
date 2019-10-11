<?php

/**
 * 数据操作类
 *
 * @author Jack Xie <xiejinci@gmail.com>
 * @copyright 2006-2010 1lele.com
 * $Id: Db.php 8 2010-03-18 08:34:29Z xiejc $
 * @history
 */
class Roc_Db_Dao
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
    public function queryByType($type,$aParam,$sAssocField)
    {
        //处理参数
        $this->$type($aParam,$sAssocField);
    }
    public function execByType($type,$aParam,$aData)
    {
        $table = $this->sDbName;
        switch ($type) {
            case 'update':
                return $this->update($table,$aData);
            case 'delete':
                return $this->delete($table,$aParam);
            case 'insert':
                return $this->insert($aData);
            case 'replace':
                return $this->replace($aData);
            case 'insertAll':
                return $this->insertAll($aData);
        }
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

    public function querySQL($sql,$sAssocField){
        return $this->getAll($sql,$sAssocField);
    }
    public function executeSQL($sql)
    {
        return $this->query($sql);
    }
    /**
     * 替换操作
     * @param unknown $table
     * @param unknown $row
     * @param string $quote
     * @return number
     */
    public function replace ($row)
    {
        return $this->insert($row,'REPLACE');
    }

    /**
     * 插入一条记录
     *
     * @param string $table 表数
     * @param array $row 数据
     * @param bool $quote 是否进行数据过滤
     * @return int 影响的条数
     */
    public function insert ($aData, $type = 'INSERT')
    {
        $cols = [];
        $vals = [];
        $table = $this->sDbName;
        foreach ($aData as $col => $val) {
            $cols[] = $col;
            $vals[] = $val;
        }
        $sql = $type . ' INTO `' . $table . '`' . '(`' . join('`, `', $cols) . '`) ' . 'VALUES ("' . join('\',\'', $vals) . '\')';
        return $this->query($sql);
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
    public function insertAll ( $rows, $type = 'INSERT')
    {
        if (empty($rows)) {
            return true;
        }

        $table = $this->sDbName;
        $n = 0;
        $cols = array();
        $vals = array();
        foreach ($rows as $row) {
            $arr = array();
            foreach ($row as $col => $val) {
                if ($n == 0) {
                    $cols[] = $col;
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
    public function update ($table, $data, $where = '')
    {
        $sets = array();
        foreach ($data as $col => $val) {
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
     * 启动事务
     * @access public
     * @return void
     */
    public function begin() {
        if ($this->iTransaction == 0) {
            if ($this->bUseCommit) {
                throw new Exception('本次操作里已经使用了一次事务。', 3);
            }
            $this->oDbh->beginTransaction();
            $this->bUseCommit = true;
        }
        $this->iTransaction ++;
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolean
     */
    public function commit() {
        if ($this->iTransaction < 1) {
            throw new Exception('出错啦！事务不配对！', 3);
        }
        $this->iTransaction --;
        if (0 == $this->iTransaction) {
            $this->oDbh->commit();
        }
        return true;
    }

    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback() {
        $this->oDbh->rollback();
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
}