<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/10/9
 * Time: 9:55
 */

abstract class Roc_Db_Driver
{
    //数据库名
    private $sDbName;
    //事务计数
    private $iTransaction = 0;
    //数据库连接
    private $oDbh = null;
    //连接时间
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
    // 查询表达式
    protected $sSelectSql = "SELECT %FIELD% FROM %TABLE%  %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% ";
    // 查询次数
    protected $queryTimes = 0;
    // 执行次数
    protected $executeTimes = 0;

    protected $bind = [];
    //参数绑定
    protected $aBind = [];
    protected static $_aOperators = array(
        '=' => 1,
        '!=' => 1,
        '<>' => 1,
        '>' => 1,
        '>=' => 1,
        '<' => 1,
        '<=' => 1,
        'IN' => 1,
        'NOTIN' => 1,
        'LIKE' => 1,
        'NOTLIKE' => 1,
        'BETWEEN' => 1
    );

    //初始化
    public function __construct($aConf)
    {
        $iStartTime = microtime(true);
        $this->oDbh = $this->connect($aConf);
        $iEndTime = microtime(true);
        self::$_iConnentTime = round(($iEndTime - $iStartTime) * 1000, 2);
    }

    public function connect($aConf)
    {
        $this->sDbName = $aConf["db"];
        $sDsn = $this->parseDsn($aConf);
        $aOption = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_STRINGIFY_FETCHES => false
        ];
        return new PDO($sDsn, $aConf['user'], $aConf['pass'], $aOption);
    }

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {

    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        $this->PDOStatement = null;
    }


    /**
     * 查询操作的底层接口
     */
    public function execute($sSQL,$sMode = PDO::FETCH_ASSOC,$bQuery = true)
    {
        $sSQL = trim($sSQL);

        $iStartTime = microtime(true);
        self::$_iQueryCnt += 1;
        //准备
        $oPDOStatement = $this->oDbh->prepare($sSQL);
        $iUseTime = round((microtime(true) - $iStartTime) * 1000, 2);
        self::$_iUseTime += $iUseTime;
        if ($oPDOStatement === false) {
            $sErrInfo = implode('|', $this->oDbh->errorInfo());
            Roc_G::throwException($sErrInfo . ": " . $sSQL);
        }
        //绑定参数查询 执行
        foreach ($this->aBind as $key => $val) {
            $oPDOStatement->bindValue($key, $val);
        }
        $result = $oPDOStatement->execute();
        if ($result === false) {
            $sErrInfo = implode('|', $oPDOStatement->errorInfo());
            Roc_G::throwException($sErrInfo . ": " . $sSQL);
        }
        // 影响记录数
        $iAffectedRows = $oPDOStatement->rowCount();
        //日志，最后的SQL
        $sSQLStr = $sSQL;
        if(!empty($this->aBind)){
            $sSQLStr = str_replace(array_keys($this->aBind),array_values($this->aBind),$sSQL);
            $this->aBind = [];
        }
        self::$_aSQL[] = $sSQLStr;
        self::_addLog($sSQLStr, $iAffectedRows, $iUseTime, $this->sDbName);
        //如果是查询的话返回数据
        if($bQuery){
            $aRows = $oPDOStatement->fetchAll($sMode);
            return $aRows;
        }else{
            //如果是执行，
            return true;
        }

    }

    /**
     * 获得所有的查询数据
     * @access private
     * @return array
     */
    private function getResult()
    {
        //返回数据集
        $result = $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 获得查询次数
     * @access public
     * @param boolean $execute 是否包含所有查询
     * @return integer
     */
    public function getQueryTimes($execute = false)
    {
        return $execute ? $this->queryTimes + $this->executeTimes : $this->queryTimes;
    }

    /**
     * 获得执行次数
     * @access public
     * @return integer
     */
    public function getExecuteTimes()
    {
        return $this->executeTimes;
    }

    /**
     * 关闭数据库
     * @access public
     */
    public function close()
    {
        $this->_linkID = null;
    }

    /**
     * 参数绑定
     * @access protected
     * @param string $name 绑定参数名
     * @param mixed $value 绑定值
     * @return void
     */
    protected function bindParam($name, $value)
    {
        $this->aBind[':' . $name] = $value;
    }

    /**
     * 字段名分析
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        return $key;
    }

    /**
     * field分析
     * @access protected
     * @param mixed $fields
     * @return string
     */
    protected function parseField($fields)
    {
        return $fields;
    }

    /**
     * table分析
     * @access protected
     * @param mixed $table
     * @return string
     */
    protected function parseTable($sTable)
    {
        return $sTable;
    }

    /**
     * where分析
     * @access protected
     * @param mixed $where
     * @return string
     */
    protected function parseWhere($where)
    {
        $sWhere = '';
        if (is_string($where)) {
            $sWhere = $where;
        } elseif (is_array($where)) {
            $aTmpWhere = [];
            foreach ($where as $k => $v) {
                if (is_numeric($k)) {    //兼容,array(0 => 'sField=1', 1 => 'sField>5')这种写法
                    $aTmpWhere[] = $v;
                } else {
                    $aTmpWhere[] = $this->parseWhereItem($k, $v);
                }
            }
            $sWhere = implode(' AND ', $aTmpWhere);
        }
        return empty($sWhere) ? '' : ' WHERE ' . $sWhere;
    }

    /**
     * Build一个字段
     * @param unknown $sKey
     * @param unknown $mValue
     * @throws Exception
     */
    public function parseWhereItem($sKey, $mValue)
    {
        //处理字段
        $aOpt = explode(' ', $sKey);
        $sOpt = strtoupper(isset($aOpt[1]) ? trim($aOpt[1]) : '=');
        $sField = trim($aOpt[0]);
        $sPrefix = "";
        if (strpos($sField, '.') !== false) {
            $aFieldTemp = explode(".", $sField, 2);
            $sPrefix = $aFieldTemp[0];
            $sField = $aFieldTemp[1];
        }
        $sRealField = $sPrefix . '`' . $sField . '`';//真正的字段
        $sBindField = $sPrefix . $sField;//绑定的字段
        //生成where
        if (isset(self::$_aOperators[$sOpt])) {
            switch ($sOpt) {
                case '=':
                case '!=':
                case '<>':
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $sRet = "{$sRealField} {$sOpt} :{$sBindField}";
                    $this->bindParam($sBindField, $mValue);
                    break;
                case 'BETWEEN':
                    if (is_string($mValue)) {
                        $aTmp = explode(',', $mValue);
                    } elseif (is_array($mValue)) {
                        $aTmp = $mValue;
                    }
                    if (count($aTmp) != 2) {
                        Roc_G::throwException("BETWEEN 参数不正确");
                    }
                    $sRet = "{$sRealField} BETWEEN :{$sBindField}1 AND :{$sBindField}2";
                    $this->bindParam($sBindField . "1", $aTmp[0]);
                    $this->bindParam($sBindField . "2", $aTmp[1]);
                    break;
                case 'IN':
                case 'NOTIN':
                    if (is_array($mValue)) {
                        $mValue = "'" . implode("','", $mValue) . "'";
                    }
                    if ($sOpt == 'IN') {
                        $sRet = "{$sRealField} IN(:{$sBindField})";
                    } else {
                        $sRet = "{$sRealField} NOT IN(:{$sBindField})";
                    }
                    $this->bindParam($sBindField, $mValue);
                    break;
                case 'LIKE':
                    $sRet = "{$sRealField} LIKE ':{$sBindField}'";
                    $this->bindParam($sBindField, $mValue);
                    break;
                case 'NOTLIKE':
                    $sRet = "{$sRealField} NOT LIKE ':{$sBindField}'";
                    $this->bindParam($sBindField, $mValue);
                    break;
            }
        } else {
            Roc_G::throwException("不支持的操作符");
        }

        return $sRet;
    }

    /**
     * limit分析
     * @access protected
     * @param mixed $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * join分析
     * @access protected
     * @param mixed $join
     * @return string
     */
    protected function parseJoin($join)
    {
        $joinStr = '';
        if (!empty($join)) {
            $joinStr = ' ' . implode(' ', $join) . ' ';
        }
        return $joinStr;
    }

    /**
     * order分析
     * @access protected
     * @param mixed $order
     * @return string
     */
    protected function parseOrder($order)
    {
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * group分析
     * @access protected
     * @param mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * having分析
     * @access protected
     * @param string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * 参数绑定分析
     * @access protected
     * @param array $bind
     * @return array
     */
    protected function parseBind($bind)
    {
        $this->bind = array_merge($this->bind, $bind);
    }
    /*
     * 分析update
     */
    protected function parseSet($aData)
    {
        $sets = [];
        foreach ($aData as $col => $val) {
            // 配制+=,-=,/=,*=的情况
            if (preg_match('/^(.+)([\+\-\/\*])=$/', $col, $tmp)) {
                $col = trim($tmp[1]);
                $opt = trim($tmp[2]);
                $sets[] = "`{$col}` = `{$col}`{$opt} :{$col}";
            } else {
                $sets[] = "`{$col}` = ':{$col}' ";
            }
            $this->bindParam(":{$col}", $val);
        }
        return " SET " . implode(', ', $sets);
    }
    /**
     * 插入记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insert($aData, $replace = false)
    {
        $values = $fields = array();
        foreach ($aData as $key => $val) {
            if (is_scalar($val)) {
                continue;
            }
            $fields[] = $key;
            $values[] = ':' . $key;
            $this->bindParam($key, $val);
        }
        $sType = true === $replace ? 'REPLACE' : 'INSERT';
        $sSQL = "{$sType} INTO `{$this->sDbName}` ('" . implode(',', $fields) . "') VALUES ('" . implode("','", $values) . "')" ;
        return $this->execute($sSQL, NULL,false);
    }


    /**
     * 批量插入记录
     * @access public
     * @param mixed $dataSet 数据集
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insertAll($dataSet, $options = array(), $replace = false)
    {
        $values = array();
        $this->model = $options['model'];
        if (!is_array($dataSet[0])) return false;
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
        $fields = array_map(array($this, 'parseKey'), array_keys($dataSet[0]));
        foreach ($dataSet as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                if (is_array($val) && 'exp' == $val[0]) {
                    $value[] = $val[1];
                } elseif (is_null($val)) {
                    $value[] = 'NULL';
                } elseif (is_scalar($val)) {
                    if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                        $value[] = $this->parseValue($val);
                    } else {
                        $name = count($this->bind);
                        $value[] = ':' . $name;
                        $this->bindParam($name, $val);
                    }
                }
            }
            $values[] = 'SELECT ' . implode(',', $value);
        }
        $sql = 'INSERT INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') ' . implode(' UNION ALL ', $values);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
    }

    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return false | integer
     */
    public function update($aData, $aOptions)
    {
        $sSQL = "UPDATE `" . $this->sDbName ."` ". $this->parseSet($aData);
        $sSQL .= $this->parseWhere(!empty($aOptions['where']) ? $aOptions['where'] : '');
        return $this->execute($sSQL, NULL,false);
    }

    /**
     * 替换SQL语句中表达式
     * @access public
     * @param array $options 表达式
     * @return string
     */
    public function buildSQL($aOption)
    {
        $sSQL = str_replace(
            ['%TABLE%', '%FIELD%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'],
            [
                $this->parseTable($aOption['table']),
                $this->parseField(!empty($aOption['field']) ? $aOption['field'] : '*'),
                $this->parseWhere(!empty($aOption['where']) ? $aOption['where'] : ''),
                $this->parseGroup(!empty($aOption['group']) ? $aOption['group'] : ''),
                $this->parseHaving(!empty($aOption['having']) ? $aOption['having'] : ''),
                $this->parseOrder(!empty($aOption['order']) ? $aOption['order'] : ''),
                $this->parseLimit(!empty($aOption['limit']) ? $aOption['limit'] : ''),
            ], $this->sSelectSql);
        return $sSQL;
    }

    /**
     * 获取最近一次查询的sql语句
     * @param string $model 模型名
     * @access public
     * @return string
     */
    public function getLastSql($model = '')
    {
        return $model ? $this->modelSql[$model] : $this->queryStr;
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID()
    {
        return $this->lastInsID;
    }

    /**
     * 获取最近的错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str SQL字符串
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
        // 释放查询
        if ($this->PDOStatement) {
            $this->free();
        }
        // 关闭连接
        $this->close();
    }

    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function begin()
    {
        if ($this->iTransaction == 0) {
            if ($this->bUseCommit) {
                Roc_Exception('本次操作里已经使用了一次事务。');
            }
            $this->oDbh->beginTransaction();
            $this->bUseCommit = true;
        }
        $this->iTransaction++;
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolean
     */
    public function commit()
    {
        if ($this->iTransaction < 1) {
            Roc_Exception('出错啦！事务不配对！');
        }
        $this->iTransaction--;
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
    public function rollback()
    {
        $this->oDbh->rollback();
        $this->iTransaction = 0;
        $this->bUseCommit = false;
        return true;
    }

    /**
     * 查询操作的底层接口
     *
     * @param string $sql
     *            要执行查询的SQL语句
     * @return Object
     */
    public function execute($sql)
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
    public function query($sql)
    {
        $res = $this->execute($sql);
        return $res->rowCount();
    }

    /**
     * 根据类型返回结果
     */
    public function queryByType($sType, $aParam, $sAssocField)
    {
        if (empty($aParam["table"])) {
            Roc_G::throwException("查询的数据库表不能为空");
        }
        $sSQL = $this->buildSQL($aParam);
        switch ($sType) {
            case 'getCol':
                return $this->getCol($sSQL);
            case 'getOne':
                return $this->getOne($sSQL);
            case 'getRow':
                return $this->getRow($sSQL);
            case 'getPair':
                return $this->getPair($sSQL);
            case 'getAll':
                return $this->getAll($sSQL, $sAssocField);
            default:
                Roc_G::throwException("不支持的查询方法");
                return null;
        }

    }

    public function execByType($type, $aParam, $aData)
    {
        switch ($type) {
            case 'update':
                return $this->update($aData,$aParam);
            case 'delete':
                return $this->delete($aParam);
            case 'insert':
                return $this->insert($aData);
            case 'replace':
                return $this->replace($aData);
            case 'insertAll':
                return $this->insertAll($aData);
            default:
                Roc_G::throwException("不支持的DB方法");
                return null;
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
    public function getAll($sSQL, $sAssocField = null)
    {
        $aList = $this->execute($sSQL, PDO::FETCH_ASSOC);
        if (empty($aList)) {
            return [];
        }
        if (null != $sAssocField) {
            $aRows = array_column($aList, null, $sAssocField);
        } else {
            $aRows = $aList;
        }

        return $aRows;
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
    public function getCol($sSQL)
    {
        $aList = $this->execute($sSQL, PDO::FETCH_NUM);
        if (!$aList) {
            return [];
        }
        $aRows = [];
        foreach ($aList as $row) {
            $aRows[] = $row[0];
        }
        return $aRows;
    }

    /**
     * 返回所有记录中以第一个字段为key,第二个字段为值的数组
     *
     * @param string $sql
     *            SQL语句
     * @return array
     */
    public function getPair($sSQL)
    {
        $aList = $this->execute($sSQL, PDO::FETCH_NUM);
        if (!$aList) {
            return [];
        }
        $aRows = [];
        foreach ($aList as $row) {
            $aRows[$row[0]] = $row[1];
        }
        return $aRows;
    }

    /**
     * 取得第一条记录
     *
     * @param string $sql
     *            SQL语句
     * @return array
     */
    public function getRow($sSQL)
    {
        $aList = $this->execute($sSQL, PDO::FETCH_ASSOC);
        if (!$aList) {
            return [];
        }
        return $aList[0];
    }

    /**
     * 取得第一条记录的第一个字段值
     *
     * @param string $sql
     *            SQL语句
     * @return int string
     */
    public function getOne($sSQL)
    {
        $aList = $this->execute($sSQL, PDO::FETCH_NUM);
        if (!$aList) {
            return null;
        }

        return $aList[0][0];
    }

    //直接查询SQL
    public function querySQL($sql, $sAssocField)
    {
        return $this->getAll($sql, $sAssocField);
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
    public function replace($row)
    {
        return $this->insert($row, 'REPLACE');
    }

    /**
     * 插入一条记录
     *
     * @param string $table 表数
     * @param array $row 数据
     * @param bool $quote 是否进行数据过滤
     * @return int 影响的条数
     */
    public function insert($aData, $type = 'INSERT')
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
    public function insertAll($rows, $type = 'INSERT')
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
     * 删除数据
     *
     * @param string $table
     *            表名
     * @param string $where
     *            条件
     * @return int
     */
    public function delete($table, $where)
    {
        return $this->query('DELETE FROM ' . $table . ' WHERE ' . $where);
    }

    /**
     * 取得最后的lastInsertId
     *
     * @return int
     */
    public function lastInsertId()
    {
        return $this->oDbh->lastInsertId();
    }

    /**
     * 设置Debug对象
     * @param unknown $p_mDebug
     */
    public static function setDebug($p_mDebug)
    {
        self::$_oDebug = $p_mDebug;
    }

    /**
     * 取得Debug统计信息
     * @return string
     */
    public static function getDebugStat()
    {
        return '[MySQL]->Query: ' . self::$_iQueryCnt . ', Connent Time: ' . self::$_iConnentTime . ' Use Time:' . self::$_iUseTime;
    }

    /**
     *
     * @param unknown $sSQL
     */
    private static function _addLog($sSQL, $iAffectedRows, $iUseTime, $sDbName)
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
    private static function _addADUSQL($sSQL)
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
            return self::$_aSQL[count(self::$_aSQL) - 1];
        }

        return '';
    }

    public function __destruct()
    {
        $this->close();
    }

    /*
     * 关闭一次操作只允许一次事务
     */
    public function closeTranction()
    {
        $this->bUseCommit = false;
    }
}
