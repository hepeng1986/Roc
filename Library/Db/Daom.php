<?php

class Db_Daom
{
    //动态的DB库
    protected static $DB_NAME = 'default';

    const PK_FIELD = 'iAutoID';

    // 分表个数
    const TABLE_NUM = 1;

    // 数据状态有效
    const STATUS_VALID = 1;

    // 数据状态无效
    const STATUS_INVALID = 0;

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
        'BETWEEN' => 1
    );

    /**
     * 取得Dbh连接
     */
    public static function getDbh ()
    {
        $class = get_called_class();
        if($class::DB_NAME){
            return Util_Common::getMySQLDB($class::DB_NAME);
        } else {
            return Util_Common::getMySQLDB(self::$DB_NAME);
        }
    }

    public static function useDb($sDbName)
    {
        self::$DB_NAME = $sDbName;
    }

    /**
     * 取得表名
     */
    public static function getTable ($iPKID = null)
    {
        $class = get_called_class();
        if ($class::TABLE_NUM > 1 && isset($iPKID)) {
            $sTblName = $class::TABLE_NAME . '_' . ($iPKID % $class::TABLE_NUM);
        } else {
            $sTblName = $class::TABLE_NAME;
        }

        return $sTblName;
    }

    /**
     * 取得主键字段
     */
    public static function getPKField ()
    {
        $class = get_called_class();
        return $class::PK_FIELD;
    }

    /**
     * 获取主键数据
     *
     * @param int $iPKID
     * @return array/null
     */
    public static function getDetail ($iPKID)
    {
        return self::getDbh()->getRow("SELECT * FROM " . self::getTable() . " WHERE " . self::getPKField() . "='$iPKID'");
    }

    /**
     * 获取主键列表
     *
     * @param array $aPKIDs
     * @return array
     */
    public static function getPKIDList ($aPKIDs, $bUsePKID = false)
    {
        if (empty($aPKIDs)) {
            return [];
        }

        $sIDs = $aPKIDs;
        if (is_array($aPKIDs)) {
            $sIDs = join(',', $aPKIDs);
        }

        $sField = null;
        if ($bUsePKID) {
            $sField = self::getPKField();
        }

        return self::getDbh()->getAll("SELECT * FROM " . self::getTable() . " WHERE " . self::getPKField() . " IN($sIDs)", $sField);
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
    public static function getAll ($aParam, $sField = null)
    {
        return self::getDbh()->getAll(self::_buildSQL($aParam), $sField);
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
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getCnt ($aParam)
    {
        return self::getDbh()->getOne(self::_buildSQL($aParam, 'COUNT(*)'));
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getOne ($aParam, $sField = null)
    {
        return self::getDbh()->getOne(self::_buildSQL($aParam, $sField, null, 1));
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getCol ($aParam, $sField = null)
    {
        return self::getDbh()->getCol(self::_buildSQL($aParam, $sField));
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getPair ($aParam, $sKeyField = null, $sValueField = null)
    {
        $sField = null;
        if (!empty($sKeyField)) {
            $sField = "$sKeyField,$sValueField";
        }
        return self::getDbh()->getPair(self::_buildSQL($aParam, $sField));
    }

    /**
     * 获取数量
     *
     * @param array $aParam
     * @return int
     */
    public static function getRow ($aParam, $sFields = '*')
    {
        return self::getDbh()->getRow(self::_buildSQL($aParam, $sFields, null, 1));
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
     * 执行SQL，还回结果
     *
     * @param string $sSQL
     * @param string $sMethod
     *            (all,row,one,col,pair,query)
     * @param string $sField
     */
    public static function query ($sSQL, $sType = 'query', $sField = null, $bCache = false)
    {
        $aMethod = array(
            'all' => 'getAll',
            'one' => 'getOne',
            'row' => 'getRow',
            'col' => 'getCol',
            'pair' => 'getPair',
            'query' => 'query'
        );

        $sMethod = $aMethod[$sType];
        if (empty($sField)) {
            $aRet = self::getDbh()->$sMethod($sSQL);
        } else {
            $aRet = self::getDbh()->$sMethod($sSQL, $sField);
        }

        return $aRet;
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
}