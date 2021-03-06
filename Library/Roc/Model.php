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
    protected static $sDbName = 'default';
    //动态表名
    protected static $sTableName = '';
    //数据库对象
    private static $_aInstance = [];
    //支持数据库
    protected static $aUsefulDb = ["mysql","sqlsrv","sqlite","oracle"];
    //参数选项
    protected static $aOpt = ["field","where","group","order","limit","having","table"];

    /**
     * 取得Dbh连接
     */
    public static function getDbh ()
    {
        $sDbName = static::$sDbName;
        if(empty($sDbName)){
            Roc_G::throwException("没有配置DB");
        }
        //缓存单例
        if (!isset(self::$_aInstance[$sDbName])) {
            $aConf = Roc_G::getConf($sDbName, 'Database');
            if(empty($aConf) || empty($aConf["type"]) || !in_array(strtolower($aConf["type"]),self::$aUsefulDb)){
                Roc_G::throwException("Db类型配置不正确");
            }
            if(empty($aConf["host"]) || empty($aConf["db"])|| empty($aConf["user"]) || empty($aConf["pass"])){
                Roc_G::throwException("Db配置不正确");
            }
            $sClassName = "Roc_Db_".ucfirst($aConf["type"]);
            self::$_aInstance[$sDbName] = new $sClassName($aConf);
        }
        return self::$_aInstance[$sDbName];
    }
    /*
     * 切换DB
     */
    public static function changeDb($sDbName)
    {
        static::$sDbName = $sDbName;
    }

    /**
     * 取得表名
     */
    public static function getTable ()
    {
        return static::$sTableName;
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
        $aParam = self::fixOpt($aParam);
        if(!empty($aParam["group"])){
            $aParam["field"] = $aParam["group"];
            $aData = self::query("getCol",$aParam);
            $iCount = count($aData);
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
        if(empty($sField)){
            return false;
        }
        $aParam = self::fixOpt($aParam);
        $aParam["field"] = $sField;
        $aParam["limit"] = "0,1";
        return self::query("getOne",$aParam);
    }
    /**
     * 获取一列
     *
     * @param array $aParam
     * @return int
     */
    public static function getCol ($aParam, $sField = null)
    {
        if(empty($sField)){
            return [];
        }
        $aParam = self::fixOpt($aParam);
        $aParam["field"] = $sField;
        return self::query("getCol",$aParam);
    }

    /**
     * 获取k-v
     *
     * @param array $aParam
     * @return int
     */
    public static function getPair ($aParam, $sKeyField = null, $sValueField = null)
    {
        if (empty($sKeyField) || empty($sValueField)) {
            return [];
        }
        $aParam = self::fixOpt($aParam);
        $sField = "{$sKeyField},{$sValueField}";
        $aParam["field"] = $sField;

        return self::query("getPair",$aParam);
    }

    /**
     * 获取一行
     *
     * @param array $aParam
     * @return int
     */
    public static function getRow ($aParam)
    {
        $aParam = self::fixOpt($aParam);
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
        $aParam = self::fixOpt($aParam);
        if($bLimit && empty($aParam["limit"])){
            $aParam["limit"] = "0,10000";
        }
        return self::query("getAll",$aParam,$sAssosField);
    }
    /*
     * 预处理参数
     * 主要是兼容参数里直接写where条件
     */
    private static function fixOpt($mParam)
    {
        $aCheckOpt = self::$aOpt;
        //如果是数组且不是纯where
        if(is_array($mParam)){
            foreach ($aCheckOpt as $item) {
                if(isset($aParam[$item])){
                    return $mParam;
                }
            }
        }
        return ["where"=>$mParam];
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
            Roc_G::throwException("不支持的查询方法");
        }
        if(empty($aParam["table"])){
            $aParam["table"] = self::getTable();
        }
        return self::getDbh()->queryByType($sType,$aParam,$sAssocField);
    }
    //目前只允许以主键更新和删除
    /**
     * 更新数据
     *
     * @param array $aData
     * @return int/false
     */
    public static function updateByPK ($aData,$pkField)
    {
        //获取pk
        if (! isset($aData[$pkField])) {
            Roc_G::throwException("更新时没有找不到主键值");
        }
        $aParam["where"][$pkField] = $aData[$pkField];
        unset($aData[$pkField]);
        return self::execute("update",$aParam, $aData);
    }

    /**
     * 物理删除
     *
     * @param int $iPKID
     * @return int/false
     */
    public static function delByPK ($pkID,$pkField)
    {
        //获取pk
        $aParam["where"][$pkField] = $pkID;
        return self::execute("delete",$aParam,null);
    }
    /**
     * 新增数据
     *
     * @param array $aData
     * @return int/false
     */
    public static function insert ($aData,&$iLastID = 0,$sType = 'INSERT')
    {
        if ($sType == 'INSERT') {
            $iRet = self::execute("insert", null,$aData);
            $iLastID = self::getLastInsertID();
            return $iRet;
        } else {
            return self::execute("replace", null,$aData);
        }
    }
    /**
     * 插入多条
     * @param unknown $rows
     * @param string $type
     */
    public static function insertAll($aRows,&$iLastID = 0)
    {
        $iRet =  self::execute("insertAll",null, $aRows);
        $iLastID = self::getLastInsertID();
        return $iRet;
    }
    /*
     * 获取最近一次的lastID
     */
    public static function getLastInsertID()
    {
        return self::getDbh()->getLastInsertID();
    }
    /*
     * 执行
     */
    private static function execute ($sType,$aParam,$aData)
    {
        $aMethod = ['update','delete','insert','replace','insertAll'];
        if(!in_array($sType,$aMethod)){
            Roc_G::throwException("不支持的执行方法");
        }
        if(empty($aParam) || empty($aParam["table"])){
            $aParam["table"] = self::getTable();
        }
        return self::getDbh()->execByType($sType,$aParam,$aData);
    }
    /**
     * 执行SQL，还回结果
     *
     * @param string $sSQL
     * @param string $sMethod
     *            (all,row,one,col,pair,query)
     * @param string $sField
     */
    public static function querySQL ($sSQL,$sAssocField = null)
    {
        return self::getDbh()->querySQL($sSQL,$sAssocField);
    }
    /*
     * 执行
     */
    public static function executeSQL ($sSQL)
    {
        return self::getDbh()->executeSQL($sSQL);
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
     * 取得最后一条SQL
     */
    public static function getLastSQL()
    {
        return self::getDbh()->getLastSQL();
    }
    /**
     * 生成查询语句
     */
    public static function buildQuerySQL($aParam)
    {
        $aParam = self::fixOpt($aParam);
        if(empty($aParam["table"])){
            $aParam["table"] = self::getTable();
        }
        return self::getDbh()->buildSQL($aParam);
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
        $aParam['where'] =  $sWhere;
        if(!empty($sField)){
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
        return self::getAll($aParam,null,false);
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
        $aParam['where'] =  $sWhere;
        if(!empty($sField)){
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
        $aRet = [];
        //获取记录
        $aRet['iTotal'] = self::getCnt(['where'=>$sWhere,'group'=>$sGroup,'table'=>$sTable]);//总数
        //获取分页数据
        $iPage = intval($iPage) ? intval($iPage) : 1;
        $iPageSize = intval($iPageSize) ? intval($iPageSize) : 20;
        $aParam['limit'] = ($iPage - 1) * $iPageSize . ',' . $iPageSize;

        //获取数据
        if($aRet['iTotal'] <= 0){
            $aRet['iPageNum'] = 0;
            $aRet['aList'] = [];
        }else{
            $aRet['iPageNum'] = ceil($aRet['iTotal']/$iPageSize);
            $aRet['aList'] = self::getAll($aParam,null,false);
        }
        return $aRet;
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
    public static function getDataListAssoc($sWhere,$mField = "*",$sGroup="",$sOrder = "",$limit = "",$sTable = ""){
        //设置参数
        $sAssocField = null;
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
                $sAssocField = $mField[1];
            }
        }
        //其他条件
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
        return self::getAll($aParam,$sAssocField,false);
    }
}