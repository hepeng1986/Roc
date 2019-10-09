<?php

/**
 * MongoDB的各种操作
 *  
 *  User: pancke@qq.com
 *  Date: 2015-11-13
 *  Time: 上午10:19:47
 */
class Db_Mongo
{
    private $oClient;
    
    private $oDb;
    
    private $oTable;
    
    private $sCurrTable; 
    
    /**
     * 构造函数
     * @param string $sServer 
     *     mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
     *     // MongoDB 服务器运行在本地 20000 端口上
     *     mongodb:///tmp/mongodb-20000.sock
     *     // 尝试连接到套接字，失败时使用 localhost 连接
     *     mongodb:///tmp/mongodb-27017.sock,localhost:27017
     * @param array $aOption
     */
    public function __construct($sServer, $aOption = array('connect' => false))
    {
        $this->oClient = new MongoClient($sServer, $aOption);
    }

    /**
     * 关闭连接
     */
    public function colse()
    {
        $this->oClient->close();
    }
    
    /**
     * 取得所有的DB
     */
    public function getDbs()
    {
        return $this->oClient->listDbs();
    }

    /**
     * 析造函数
     */
    public function __destruct()
    {
        $this->oClient->close();
    }
    
    /****************************************/
    /*************以下为DB操作*****************/
    /****************************************/
    /**
     * 选择一个DB
     * @param unknown $sDb
     */
    public function selectDb($sDb) 
    {
        $this->oDb = $this->oClient->selectDb($sDb);
    }

    /**
     * 命令
     * @param unknown $aCommand
     * @param unknown $aOption
     */
    public function command($aCommand, $aOption = array()) 
    {
        return $this->oDb->command($aCommand, $aOption);
    }
    
    /**
     * 取得所有的表名
     * @param unknown $aOption
     */
    public function getTables($aOption = array())
    {
        return $this->oDb->getCollectionNames($aOption);
    }
    
    /**
     * Execute
     * @param unknown $sTable
     * @param unknown $mCode
     * @param unknown $aArgs
     */
    public function execute($mCode, $aArgs = array())
    {
        return $this->oDb->execute($mCode, $aArgs);
    }
    
    /****************************************/
    /*************以下为TABLE操作**************/
    /****************************************/
    /**
     * 选择一个Table
     * @param unknown $sTable
     */
    public function selectTable($sTable)
    {
        if ($this->sCurrTable != $sTable) {
            $this->oTable = $this->oDb->selectCollection($sTable);
        }
        
        return $this->oTable;
    }
    
    /**
     * 插入数据
     * @param unknown $sTable
     * @param unknown $aData
     * @param unknown $aOption
     */
    public function insert($sTable, $aData, $aOption = array())
    {
        $this->selectTable($sTable);
        return $this->oTable->insert($aData, $aOption);
    }
    
    /**
     * 更新数据
     * @param unknown $sTable
     * @param unknown $aWhere
     * @param unknown $aData
     * @param unknown $aOption
     */
    public function update($sTable, $aWhere, $aData, $aOption = array())
    {
        $this->selectTable($sTable);
        return $this->oTable->update($aWhere, $aData, $aOption);        
    }
    
    /**
     * 删除数据
     * @param unknown $sTable
     * @param unknown $aWhere
     * @param unknown $aOption
     */
    public function delete($sTable, $aWhere, $aOption = array())
    {
        $this->selectTable($sTable);
        return $this->oTable->remove($aWhere, $aOption); 
    }
    
    /**
     * GROUP BY
     * @param unknown $sTable
     * @param unknown $aKey
     * @param unknown $aInitial
     * @param unknown $sReduce
     */
    public function group($sTable, $aKey, $aInitial, $sReduce)
    {
        $this->selectTable($sTable);
        return $this->oTable->remove($aKey, $aInitial, $sReduce); 
    }
    
    /**
     * Distinct
     * @param unknown $sTable
     * @param unknown $sField
     * @param unknown $aWhere
     */
    public function distinct($sTable, $sField, $aWhere) 
    {
        $this->selectTable($sTable);
        return $this->oTable->distinct($sField, $aWhere);
    }
    
    /**
     * getOne
     * @param unknown $sTable 表名
     * @param unknown $aWhere 条件
     * @param unknown $aOrder 排序
     * @param number $iLimit Limit
     * @param number $iSkip Offset
     * @param unknown $aField 字段
     * @return Ambigous <mixed, multitype:multitype: Ambigous <> unknown >
     */
    public function getOne($sTable, $aWhere = array(), $aOrder = array(), $iLimit = 0, $iSkip = 0, $aField = array())
    {
        return $this->query($sTable, $aWhere, $aOrder, $aField, 'one');
    }

    /**
     * getRow
     * @param unknown $sTable 表名
     * @param unknown $aWhere 条件
     * @param unknown $aOrder 排序
     * @param number $iLimit Limit
     * @param number $iSkip Offset
     * @param unknown $aField 字段
     * @return Ambigous <mixed, multitype:multitype: Ambigous <> unknown >
     */
    public function getRow($sTable, $aWhere = array(), $aOrder = array(), $iLimit = 0, $iSkip = 0, $aField = array())
    {
        return $this->query($sTable, $aWhere, $aOrder, $aField, 'row');
    }

    /**
     * getCol
     * @param unknown $sTable 表名
     * @param unknown $aWhere 条件
     * @param unknown $aOrder 排序
     * @param number $iLimit Limit
     * @param number $iSkip Offset
     * @param unknown $aField 字段
     * @return Ambigous <mixed, multitype:multitype: Ambigous <> unknown >
     */
    public function getCol($sTable, $aWhere = array(), $aOrder = array(), $iLimit = 0, $iSkip = 0, $aField = array())
    {
        return $this->query($sTable, $aWhere, $aOrder, $iLimit, $iSkip, $aField, 'col');
    }

    /**
     * getPair
     * @param unknown $sTable 表名
     * @param unknown $aWhere 条件
     * @param unknown $aOrder 排序
     * @param number $iLimit Limit
     * @param number $iSkip Offset
     * @param unknown $aField 字段
     * @return Ambigous <mixed, multitype:multitype: Ambigous <> unknown >
     */
    public function getPair($sTable, $aWhere = array(), $aOrder = array(), $iLimit = 0, $iSkip = 0, $aField = array())
    {
        return $this->query($sTable, $aWhere, $aOrder, $iLimit, $iSkip, $aField, 'pair');
    }

    /**
     * getAll
     * @param unknown $sTable 表名
     * @param unknown $aWhere 条件
     * @param unknown $aOrder 排序
     * @param number $iLimit Limit
     * @param number $iSkip Offset
     * @param unknown $aField 字段
     * @return Ambigous <mixed, multitype:multitype: Ambigous <> unknown >
     */
    public function getAll($sTable, $aWhere = array(), $aOrder = array(), $iLimit = 0, $iSkip = 0, $aField = array())
    {
        return $this->query($sTable, $aWhere, $aOrder, $iLimit, $iSkip, $aField, 'all');
    }
    
    /**
     * 查询结果
     * @param unknown $sTable
     * @param unknown $aWhere
     * @param unknown $aField
     */
    public function query($sTable, $aWhere = array(), $aOrder = array(), $iLimit = 0, $iSkip = 0, $aField = array(), $sFetchType = 'all')
    {
        $this->selectTable($sTable);
        $oCursor = $this->oTable->find($aWhere, $aField);
        if (! empty($aOrder)) {
            $oCursor->sort($aOrder);
        }
        if ($iSkip > 0) {
            $oCursor->skip($iSkip);
        }
        if ($iLimit > 0) {
            $oCursor->limit($iLimit);
        }
        
        $aRet = array();
        switch ($sFetchType) {
            case 'row':
                $aRet = $oCursor->current();
                break;
            case 'one':
                $aRet = $oCursor->current();
                $aRet = current($aRet);
                break;
            case 'pair':
                foreach ($oCursor as $aRow) {
                    $aRow = array_values($aRow);
                    $aRet[$aRow[0]] = $aRow[1];
                }
                break;
            case 'col':
                foreach ($oCursor as $aRow) {
                    list ($k, $v) = each($aRow);
                    $aRet[] = $v;
                }
                break;
            case 'all':
            default:
                foreach ($oCursor as $aRow) {
                    $aRet[] = $aRow;
                }
                break;
        }
        
        return $aRet;
    }
    
    /**
     * 统计结果
     * @param unknown $sTable
     * @param unknown $aWhere
     * @param number $iLimit
     * @param number $iSkip
     */
    public function count($sTable, $aWhere = array(), $iLimit = 0, $iSkip = 0)
    {
        $this->selectTable($sTable);
        return $this->oTable->count($aWhere, $iLimit, $iSkip);
    }
    
    /**
     * SAVE操作
     * @param unknown $sTable
     * @param unknown $aData
     * @param unknown $aOption
     */
    public function save($sTable, $aData, $aOption = array())
    {
        $this->selectTable($sTable);
        return $this->oTable->save($aData, $aOption);
    }
}