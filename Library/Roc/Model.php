<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/9/29
 * Time: 13:35
 */

class Roc_Model
{
    //动态的DB库
    protected static $dbName = 'default';
    //动态表名
    protected static $tableName = '';
    //主键
    protected static $pkField = 'iAutoID';

    /**
     * 取得Dbh连接
     */
    public static function getDbh ()
    {
        return Db_Dao::getInstance(static::$dbName);
    }
    /*
     * 切换DB
     */
    public static function changeDb($sDbName)
    {
        static::$dbName = $sDbName;
    }

    /**
     * 取得表名
     */
    public static function getTable ()
    {
        return static::$tableName;
    }

    /**
     * 取得主键字段
     */
    public static function getPKField ()
    {
        return static::$pkField;
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getCnt ($aParam)
    {
        $iCount = 0;
        if(!empty($aParam["group"])){
            $aParam["field"] = "1";
            $aRet = self::query("getCol",$aParam);
            $iCount = count($aRet);
        }else{
            $aParam["field"] = "COUNT(*)";
            $iCount = self::query("getOne",$aParam);
        }
        return $iCount;
    }
    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getOne ($aParam, $sField = null)
    {
        if(Roc_G::emptyZero($sField)){
            return [];
        }
        $aParam["field"] = $sField;
        return self::query("getOne",$aParam);
    }
    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getCol ($aParam, $sField = null)
    {
        if(Roc_G::emptyZero($sField)){
            return [];
        }
        $aParam["field"] = $sField;
        return self::query("getCol",$aParam);
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getPair ($aParam, $sKeyField = null, $sValueField = null)
    {
        if (Roc_G::emptyZero($sKeyField) || Roc_G::emptyZero($sValueField)()) {
            return [];
        }
        $sField = "{$sKeyField},{$sValueField}";
        $aParam["field"] = $sField;

        return self::query("getPair",$aParam);
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getRow ($aParam)
    {
        $aParam["limit"] = "0,1";
        return self::query("getRow",$aParam);
    }
    /**
     * 获取列表
     *
     * @param array $aParam
     * @param string $sOrder
     * @return array
     */
    public static function getAll ($aParam, $sAssosField = null,$bLimit = true)
    {
        if($bLimit){
            $aParam["limit"] = "0,2000";
        }
        return self::query("getAll",$aParam,$sAssosField);
    }
    /**
     * 执行SQL，还回结果
     *
     * @param string $sSQL
     * @param string $sMethod
     *            (all,row,one,col,pair,query)
     * @param string $sField
     */
    private static function query ($sType,$aParam,$sAssocField = null)
    {
        $aMethod = ['getAll','getOne','getRow','getCol','getPair'];
        if(!in_array($sType,$aMethod)){
            return [];
        }
        $oDb = self::getDbh();
        if(!method_exists($oDb,$sType)){
            return "getOne" == $sType?0:[];
        }
        return $oDb->$sType($aParam,$sAssocField);
    }
    /**
     * 获取主键数据
     *
     * @param int $iPKID
     * @return array/null
     */
    public static function getDetail ($iPKID,$sField="*")
    {
        $aParam["table"] = self::getTable();
        if(empty($sField)){
            $aParam["field"] = "*";
        }else{
            $aParam["field"] = $sField;
        }
        //获取pk
        $pkField = self::getPKField();
        $aParam["where"][$pkField] = $iPKID;
        return self::query("getRow",$aParam);
    }

    /**
     * 获取主键列表
     *
     * @param array $aPKIDs
     * @return array
     */
    public static function getPKIDList ($pkFieldList, $bUsePKID = false)
    {
        if (empty($aPKIDs)) {
            return [];
        }
        $pkField = self::getPKField();
        $sAssocField = $bUsePKID?$pkField:null;
        $aParam["{$pkField} IN"] = $pkFieldList;

        return self::query("getAll",$aParam,$sAssocField);
    }

    /**
     * 新增数据
     *
     * @param array $aData
     * @return int/false
     */
    public static function addData ($aData, $bQuote = true, $sType = 'INSERT')
    {
        if (! isset($aData['iCreateTime'])) {
            $aData['iCreateTime'] = time();
        }
        if (! isset($aData['iUpdateTime'])) {
            $aData['iUpdateTime'] = time();
        }
        if (! isset($aData['iStatus'])) {
            $aData['iStatus'] = self::STATUS_VALID;
        }

        if ($sType == 'INSERT') {
            self::getDbh()->insert(self::getTable(), $aData, $bQuote);
        } else {
            self::getDbh()->replace(self::getTable(), $aData, $bQuote);
        }
        return self::getDbh()->lastInsertId();
    }

    /**
     * 如果记录存在则替换，不存在则插入
     * @param unknown $aData
     */
    public static function replace($aData, $bQuote = true)
    {
        return self::addData($aData, $bQuote, 'REPLACE');
    }

    /**
     * 插入多条
     * @param unknown $rows
     * @param string $type
     */
    public static function insertRows($aRows, $bQuote = true, $sType = 'INSERT')
    {
        return self::getDbh()->insertRows(self::getTable(), $aRows, $sType, $bQuote);
    }

    /**
     * 更新数据
     *
     * @param array $aData
     * @return int/false
     */
    public static function updData ($aData, $quote=true)
    {
        $sPKField = self::getPKField();
        if (! isset($aData[$sPKField])) {
            throw new Exception("更新时没有找不到主键值");
        }

        $sWhere = "$sPKField='" . $aData[$sPKField] . "'";
        if (! isset($aData['iUpdateTime'])) {
            $aData['iUpdateTime'] = time();
        }

        return self::getDbh()->update(self::getTable(), $aData, $sWhere, $quote);
    }

    /**
     * 逻辑删除
     *
     * @param int $iPKID
     * @return int/false
     */
    public static function delData ($iPKID)
    {
        $sPKField = self::getPKField();
        $sTable = self::getTable();
        return self::getDbh()->query("UPDATE $sTable SET iStatus=" . self::STATUS_INVALID . ",iUpdateTime=" . time() . " WHERE $sPKField='$iPKID'");
    }

    /**
     * 物理删除
     *
     * @param unknown $iPKID
     */
    public static function realDel ($iPKID)
    {
        $sPKField = self::getPKField();
        $sTable = self::getTable();
        return self::getDbh()->query("DELETE FROM $sTable WHERE $sPKField='$iPKID'");
    }

    /**
     * 获取列表
     *
     * @param array $aParam
     * @param string $sOrder
     * @return array
     */
    public static function getList ($aParam, $iPage, $sOrder = '', $iPageSize = 20, $sUrl = '', $aArg = array(), $bReturnPager = true, $sColunm = '*')
    {
        $iPage = max($iPage, 1);
        $sLimit = ($iPage - 1) * $iPageSize . ',' . $iPageSize;
        $sSQL = self::_buildSQL($aParam, $sColunm, $sOrder, $sLimit);

        $aRet = array();
        $aRet['aList'] = self::getDbh()->getAll($sSQL);
        if ($iPage == 1 && count($aRet['aList']) < $iPageSize) {
            $aRet['iTotal'] = count($aRet['aList']);

            if ($bReturnPager) {
                $aRet['aPager'] = null;
            }
        } else {
            if (is_array($aParam)) {
                unset($aParam['limit'], $aParam['order']);
                if (isset($aParam['field'])) {
                    $aParam['field'] = 'COUNT(*)';
                }
            }
            $sSQL = self::_buildSQL($aParam, 'COUNT(*)');
            $aRet['iTotal'] = self::getDbh()->getOne($sSQL);

            if ($bReturnPager) {
                if (empty($sUrl)) {
                    $sUrl = Util_Common::getUrl(null, null, true);
                }
                if (empty($aArg)) {
                    $aArg = $_REQUEST;
                }
                $aRet['aPager'] = Util_Page::getPage($aRet['iTotal'], $iPage, $iPageSize, $sUrl, $aArg);
            }
        }

        return $aRet;
    }


    /**
     * 返回结果并缓存数据
     * @param unknown $sSQL
     * @param string $sType
     * @param string $sField
     */
    public static function getSQLCache ($sSQL, $iExpire = 3600, $sType = 'all', $sField = null, $sPrefix = '')
    {
        $oCache = self::getCache();
        $sCacheKey = $sPrefix . md5($sSQL);
        $aRet = $oCache->get($sCacheKey);
        if (empty($aRet)) {
            $aRet = self::query($sSQL, $sType, $sField);
            $oCache->set($sCacheKey, $aRet, $iExpire);
        }

        return $aRet;
    }

    /**
     * 取得DB的Cache
     */
    public static function getCache()
    {
        return Util_Common::getCache();
    }



    /**
     * 事务开始
     */
    public static function begin ()
    {
        return self::getDbh()->begin();
    }

    /**
     * 事务提交
     */
    public static function commit ()
    {
        return self::getDbh()->commit();
    }

    /**
     * 事务回滚
     */
    public static function rollback ()
    {
        return self::getDbh()->rollback();
    }

    /**
     * 构建过滤的条件
     *
     * @param array $aParam
     * @return string
     */
    private static function _buildSQL ($aParam, $sField = '*', $sOrder = null, $sLimit = null)
    {
        // 如果传入的本身是一条SELECT的SQL，则直接返回，用于兼容原生的getOne,getCol,getPair,getAll,getRow
        if (is_string($aParam)) {
            $aParam = trim($aParam);
            if (strtoupper(substr($aParam, 0, 6)) == 'SELECT') {
                $sSQL = $aParam;
                if (!empty($sField) && $sField != '*') {
                    $sSQL = str_replace('*', $sField, $sSQL);
                }
                if ($sOrder != '' && false === stripos($sSQL, 'ORDER BY')) {
                    $sSQL .= ' ORDER BY ' . $sOrder;
                }
                if ($sLimit != '' && false === stripos($sSQL, 'LIMIT')) {
                    $sSQL .= ' LIMIT ' . $sLimit;
                }
                return $sSQL;
            }
        }

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
                case 'BETWEEN':
                    if (is_string($mValue)) {
                        $aTmp = explode(',', $mValue);
                    } else {
                        $aTmp = $mValue;
                    }
                    $sRet = "$sField BETWEEN {$aTmp[0]} AND {$aTmp[1]}";
                    break;
                case 'IN':
                case 'NOTIN':
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
            }
        } else {
            throw new Exception("Unkown operator $sOpt!!!");
        }

        return $sRet;
    }

    /**
     * 数据过滤
     *
     * @param mixed $value
     *            要过滤的值
     * @return string
     */
    public static function quote ($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        }
        return addcslashes($value, "\000\n\r\\'\"\032");
    }

    /**
     * 取得最后一条SQL
     */
    public static function getLastSQL()
    {
        return self::getDbh()->getLastSQL();
    }
    /**
     * 根据查询条件，从数据库获取数据列表 getAll子集
     * @param  sWhere  查询条件
     * @param  sField  查询字段
     * @param  sGroup  分组
     * @param  sOrder  排序
     * @param  sTable  表名，默认为调用模型的TABLE_NAME
     * @return array
     */
    public static function getDataList($sWhere,$sField = "*",$sGroup="",$sOrder = "",$limit = "", $sTable = ""){
        //设置参数
        $aParam = [];
        $aParam['where'] =  $sWhere;
        if(!empty($sField) && $sField !="*"){
            $aParam['field'] =  $sField;
        }
        if(!empty($sGroup)){
            $aParam['group'] =  $sGroup;
        }
        if(!empty($sOrder)){
            $aParam['order'] =  $sOrder;
        }
        if(!empty($limit)){
            $aParam['limit'] = $limit;
        }
        if(!empty($sTable)){
            $aParam['table'] =  $sTable;
        }
        return self::getAll($aParam);
    }
    /**
     * 根据查询条件，从数据库获取分页数据列表
     * @param  sWhere  查询条件
     * @param  iPage   第几页
     * @param  sField  查询字段
     * @param  sGroup  分组
     * @param  sOrder  排序
     * @param  sTable  表名，默认为调用模型的TABLE_NAME
     * @param  iPageSize  每页大小
     * @return array  包含total,pagenum,data
     */
    public static function getDataListPage($sWhere,$iPage=1,$sField = "*",$sGroup="",$sOrder = "",$sTable = "",$iPageSize=20){
        //设置参数
        $aParam = [];
        $aParam['where'] =  $sWhere;

        if(!empty($sField) && $sField !="*"){
            $aParam['field'] =  $sField;
        }
        if(!empty($sGroup)){
            $aParam['group'] =  $sGroup;
        }
        if(!empty($sOrder)){
            $aParam['order'] =  $sOrder;
        }
        if(!empty($sTable)){
            $aParam['table'] =  $sTable;
        }
        //返回
        $ret = [];
        //获取记录
        if(empty($sGroup)){
            $ret['iTotal'] = self::getCnt(['where'=>$sWhere,'table'=>$sTable]);//总数
        }else{
            //分组不可以用count(*)
            $temp = self::getAll(['where'=>$sWhere,'table'=>$sTable,'group'=>$sGroup,'field'=>$sGroup]);
            $ret['iTotal'] = count($temp);
        }
        //获取分页数据
        $iPage = intval($iPage) ? intval($iPage) : 1;
        $iPageSize = intval($iPageSize) ? intval($iPageSize) : 20;
        $aParam['limit'] = ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        //获取数据
        $ret['iPageNum'] = ceil($ret['iTotal']/$iPageSize);
        $ret['aList'] = self::getAll($aParam);

        return $ret;
    }
    /**
     * 根据查询条件，从数据库获取数据列表 返回关联数组 getAll子集
     * @param  sWhere  查询条件
     * @param  mField  查询字段 mField[0] 字段字符串,mField[1] 字段名
     * @param  sGroup  分组
     * @param  sOrder  排序
     * @param  sTable  表名，默认为调用模型的TABLE_NAME
     * @return array
     */
    public static function getDataListAssoc($sWhere,$mField = "*",$sGroup="",$sOrder = "",$sTable = ""){
        //设置参数
        $assoc = null;
        $aParam = [];
        $aParam['where'] =  $sWhere;
        //获取key
        if(is_string($mField)){
            if(!empty($mField) && $mField !="*"){
                $aParam['field'] =  $mField;
            }
        }elseif(is_array($mField)){
            if(!empty($mField[0]) && $mField[0] !="*"){
                $aParam['field'] =  $mField[0];
            }
            if(!empty($mField[1])){
                $assoc = $mField[1];
            }
        }
        //其他条件
        if(!empty($sGroup)){
            $aParam['group'] =  $sGroup;
        }
        if(!empty($sOrder)){
            $aParam['order'] =  $sOrder;
        }
        if(!empty($sTable)){
            $aParam['table'] =  $sTable;
        }
        return self::getAll($aParam,$assoc);
    }
    public static function  closeTranction(){
        self::getDbh()->closeTranction();
    }
}