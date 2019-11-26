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
    private $sDbName = "";
    //事务计数
    private $iTransaction = 0;
    //数据库连接
    private $oDbh = null;
    //SQL语句
    private static $_aSQL = array();
    //查询次数
    private static $_iQueryCnt = 0;
    //连接时间
    private static $_iConnentTime = 0;
    //查询总耗时
    private static $_iUseTime = 0;
    //数据库类型
    private static $sDbType = "";
    // 查询表达式
    protected $sSelectSql = "SELECT %FIELD% FROM %TABLE%  %WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% ";
    //最后插入ID
    protected $iLastInsertId = 0;
    //参数绑定
    protected $aBind = [];
    //支持的运算符
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
        Roc_Debug::register([get_called_class(), 'getDebugStat']);
    }

    public function connect($aConf)
    {
        $this->sDbName = $aConf["db"];
        $this->sDbType = $aConf["type"];
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
     * 查询操作的底层接口
     */
    public function execute($sSQL,&$iAffectedRows = 0)
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
        $sSQLStr = $this->_formatSQL($sSQL);
        //清除绑定参数
        $this->aBind = [];
        self::$_aSQL[] = $sSQLStr;
        self::_addLog($sSQLStr, $iAffectedRows, $iUseTime, $this->sDbName);
        //返回对象
        return $oPDOStatement;
    }
    /*
     * 获取SQL
     */
    private function _formatSQL($sSQL){
        $sSQLStr = $sSQL;
        if (!empty($this->aBind)) {
            $sSQLStr = str_replace(array_keys($this->aBind), array_values($this->aBind), $sSQL);
        }
        return $sSQLStr;
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
            $iIndex = 0;//相同字段索引
            foreach ($where as $k => $v) {
                if (is_numeric($k)) {    //兼容,array(0 => 'sField=1', 1 => 'sField>5')这种写法
                    $aTmpWhere[] = $v;
                } else {
                    $aTmpWhere[] = $this->parseWhereItem($k, $v,$iIndex);
                }
                $iIndex++;
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
    public function parseWhereItem($sKey, $mValue,$iIndex)
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
        $sBindField = $sField.$iIndex;//绑定的字段
        $sRet = "";
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
                    $sRet = "{$sRealField} BETWEEN :{$sBindField}A AND :{$sBindField}B";
                    $this->bindParam($sBindField . "A", $aTmp[0]);
                    $this->bindParam($sBindField . "B", $aTmp[1]);
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
            $this->bindParam($col, $val);
        }
        return implode(', ', $sets);
    }

    /**
     * @param $sTableName string
     * @param $aData array
     * @param $bReplace bool
     * @return int
     */
    public function insert($sTableName, $aData, $bReplace = false)
    {
        $values = $fields = [];
        foreach ($aData as $key => $val) {
            if (!is_scalar($val)) {
                continue;
            }
            $fields[] = $key;
            $values[] = ":{$key}";
            $this->bindParam($key, $val);
        }
        $sType = $bReplace ? 'REPLACE' : 'INSERT';
        $sSQL = "{$sType} INTO `{$sTableName}` (`" . implode('`,`', $fields) . "`) VALUES ('" . implode("','", $values) . "')";
        $this->execute($sSQL,$iAffectedRows);
        //获取最后ID
        $iNum = $this->oDbh->lastInsertId();
        if (is_numeric($iNum)) {
            $this->iLastInsertId = $iNum;
        }
        return $iAffectedRows;
    }

    /**
     * 替换操作
     * @param unknown $table
     * @param unknown $row
     * @param string $quote
     * @return number
     */
    public function replace($sTableName, $aData)
    {
        return $this->insert($sTableName, $aData, true);
    }

    /**
     * 批量插入记录
     * @access public
     * @param mixed $dataSet 数据集
     * @param array $options 参数表达式
     * @param boolean $replace 是否replace
     * @return false | integer
     */
    public function insertAll($sTableName, $aData)
    {
        if (empty($aData)) {
            return true;
        }
        $n = 0;
        $cols = [];
        $vals = [];
        foreach ($aData as $row) {
            $arr = [];
            foreach ($row as $col => $val) {
                if (0 == $n) {
                    $cols[] = $col;
                }
                $arr[] = $val;
            }
            $vals[] = "('" . implode("','", $arr) . "')";
            $n++;
        }
        $sSQL = "INSERT INTO `{$sTableName}` (`" . implode("`,`", $cols) . "`) VALUES " . implode(",", $vals);
        $this->execute($sSQL,$iAffectedRows);
        //获取最后ID
        $iNum = $this->oDbh->lastInsertId();
        if (is_numeric($iNum)) {
            $this->iLastInsertId = $iNum;
        }
        return $iAffectedRows;
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
    public function delete($sTableName, $aOptions)
    {
        if(empty($aOptions)){
            Roc_G::throwException("不能删除表的所有数据");
        }
        $sSQL = "DELETE FROM `{$sTableName}` ";
        $sSQL .= $this->parseWhere(!empty($aOptions['where']) ? $aOptions['where'] : '');
        $iAffectedRows = 0;
        $this->execute($sSQL,$iAffectedRows);
        return $iAffectedRows;
    }

    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return false | integer
     */
    public function update($sTableName, $aData, $aOptions)
    {
        //SQL语句
        $sSQL = "UPDATE `{$sTableName}` SET " . $this->parseSet($aData);
        //条件
        $sSQL .= $this->parseWhere(!empty($aOptions['where']) ? $aOptions['where'] : '');
        $iAffectedRows = 0;
        $this->execute($sSQL,$iAffectedRows);
        return $iAffectedRows;
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
     * 获取最近插入的ID
     * @access public
     * @return string
     */
    public function getLastInsertID()
    {
        return $this->iLastInsertId;
    }

    /**
     * 启动事务
     * @access public
     * @return bool
     */
    public function begin()
    {
        if ($this->iTransaction > 0) {//不允许嵌套事务
            Roc_G::throwException('出错啦！已有事务未结束！');
        }
        $this->iTransaction++;
        $this->oDbh->beginTransaction();
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return boolean
     */
    public function commit()
    {
        if ($this->iTransaction != 1) {//不允许嵌套事务
            Roc_G::throwException('出错啦！事务不配对！');
        }
        $this->iTransaction--;
        $this->oDbh->commit();
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
        return true;
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
        $oPDOStatement = $this->execute($sSQL);
        if(!$oPDOStatement){
            return [];
        }
        $aList = $oPDOStatement->fetchAll(PDO::FETCH_ASSOC);
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
        $oPDOStatement = $this->execute($sSQL);
        if(!$oPDOStatement){
            return [];
        }
        $aList = $oPDOStatement->fetchAll(PDO::FETCH_NUM);
        if (!$aList) {
            return [];
        }
        $aRows = array_column($aList,0);
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
        $oPDOStatement = $this->execute($sSQL);
        if(!$oPDOStatement){
            return [];
        }
        $aList = $oPDOStatement->fetchAll(PDO::FETCH_NUM);
        if (!$aList) {
            return [];
        }
        $aRows = array_column($aList,1,0);
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
        $oPDOStatement = $this->execute($sSQL);
        if(!$oPDOStatement){
            return [];
        }
        $aList = $oPDOStatement->fetch(PDO::FETCH_ASSOC);
        if (!$aList) {
            return [];
        }
        return $aList;
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
        $oPDOStatement = $this->execute($sSQL);
        if(!$oPDOStatement){
            return null;
        }
        $sRet = $oPDOStatement->fetchColumn(0);
        if (false === $sRet) {
            return null;
        }

        return $sRet;
    }

    //直接查询SQL
    public function querySQL($sSQL, $sAssocField = null)
    {
        return $this->getAll($sSQL, $sAssocField);
    }
    /*
     * 执行SQL
     */
    public function execByType($type, $aParam, $aData)
    {
        if (empty($aParam["table"])) {
            Roc_G::throwException("数据库表不能为空");
        }
        $sTableName = $aParam["table"];
        switch ($type) {
            case 'update':
                return $this->update($sTableName, $aData, $aParam);
            case 'delete':
                return $this->delete($sTableName, $aParam);
            case 'insert':
                return $this->insert($sTableName, $aData);
            case 'replace':
                return $this->replace($sTableName, $aData);
            case 'insertAll':
                return $this->insertAll($sTableName, $aData);
            default:
                Roc_G::throwException("不支持的DB方法");
                return null;
        }
    }
    /*
     * 直接执行SQL
     */
    public function executeSQL($sSQL)
    {
        $sSQL = trim($sSQL);

        $iStartTime = microtime(true);
        self::$_iQueryCnt += 1;
        //准备
        $iRet = $this->oDbh->exec($sSQL);
        $iUseTime = round((microtime(true) - $iStartTime) * 1000, 2);
        self::$_iUseTime += $iUseTime;
        if ($iRet === false) {
            $sErrInfo = implode('|', $this->oDbh->errorInfo());
            Roc_G::throwException($sErrInfo . ": " . $sSQL);
        }
        self::$_aSQL[] = $sSQL;
        self::_addLog($sSQL, $iRet, $iUseTime, $this->sDbName);
        //返回影响的行数
        return $iRet;
    }

    /**
     * 取得Debug统计信息
     * @return string
     */
    public static function getDebugStat()
    {
        return '['.self::$sDbType.']->Query: ' . self::$_iQueryCnt . ', Connent Time: ' . self::$_iConnentTime . ' Use Time:' . self::$_iUseTime;
    }

    /**
     *
     * @param unknown $sSQL
     */
    private static function _addLog($sSQL, $iAffectedRows, $iUseTime, $sDbName)
    {
        if (count(self::$_aSQL) <= 300) {
            self::$_aSQL[] = $sSQL;
        }
        Roc_Debug::add('[DB->' . $sDbName . ']: ' . $sSQL . ' AffectedRows:' . $iAffectedRows . ' Use Time:' . $iUseTime . '毫秒');
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
}
