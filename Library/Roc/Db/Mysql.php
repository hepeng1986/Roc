<?php

/**
 * mysql数据库驱动
 */
class Roc_Db_Mysql extends Roc_Db_Driver
{

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($aConf)
    {
        $sDsn = "mysql:host={$aConf['host']};dbname={$aConf['db']}";
        if (!empty($aConf["port"])) {
            $sDsn .= ";port={$aConf['port']}";
        }
        return $sDsn;
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }

    /**
     * @param $sTableName 表名
     * @param $aData 数组
     * @return int  受影响的行数
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
        $this->execute($sSQL, $iAffectedRows);
        //获取最后ID
        $iNum = $this->oDbh->lastInsertId();
        if (is_numeric($iNum)) {
            $this->iLastInsertId = $iNum;
        }
        return $iAffectedRows;
    }
}
