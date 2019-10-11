<?php
/**
 * Oracle数据库驱动
 */
class Roc_Db_Oracle extends Roc_Db_Driver{

    private     $table        = '';
    protected   $selectSql    = 'SELECT * FROM (SELECT thinkphp.*, rownum AS numrow FROM (SELECT  %DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%) thinkphp ) %LIMIT%%COMMENT%';

    /**
     * 解析pdo连接的dsn信息
     * @access public
     * @param array $config 连接信息
     * @return string
     */
    protected function parseDsn($config){
        $dsn  =   'oci:dbname=//'.$config['hostname'].($config['hostport']?':'.$config['hostport']:'').'/'.$config['database'];
        if(!empty($config['charset'])) {
            $dsn  .= ';charset='.$config['charset'];
        }
        return $dsn;
    }

    /**
     * 执行语句
     * @access public
     * @param string $str  sql指令
     * @param boolean $fetchSql  不执行只是获取SQL     
     * @return integer
     */
    public function execute($str,$fetchSql=false) {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        $this->queryStr = $str;
        if(!empty($this->bind)){
            $that   =   $this;
            $this->queryStr =   strtr($this->queryStr,array_map(function($val) use($that){ return '\''.$that->escapeString($val).'\''; },$this->bind));
        }
        if($fetchSql){
            return $this->queryStr;
        }
        $flag = false;
        if(preg_match("/^\s*(INSERT\s+INTO)\s+(\w+)\s+/i", $str, $match)) {
            $this->table = C("DB_SEQUENCE_PREFIX").str_ireplace(C("DB_PREFIX"), "", $match[2]);
            $flag = (boolean)$this->query("SELECT * FROM user_sequences WHERE sequence_name='" . strtoupper($this->table) . "'");
        }
        //释放前次的查询结果
        if ( !empty($this->PDOStatement) ) $this->free();
        $this->executeTimes++;
        N('db_write',1); // 兼容代码        
        // 记录开始执行时间
        $this->debug(true);
        $this->PDOStatement	=	$this->_linkID->prepare($str);
        if(false === $this->PDOStatement) {
            $this->error();
            return false;
        }
        foreach ($this->bind as $key => $val) {
            if(is_array($val)){
                $this->PDOStatement->bindValue($key, $val[0], $val[1]);
            }else{
                $this->PDOStatement->bindValue($key, $val);
            }
        }
        $this->bind =   array();        
        $result	=	$this->PDOStatement->execute();
        $this->debug(false);
        if ( false === $result) {
            $this->error();
            return false;
        } else {
            $this->numRows = $this->PDOStatement->rowCount();
            if($flag || preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsID = $this->_linkID->lastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 取得数据库的表信息（暂时实现取得用户表信息）
     * @access public
     */
    public function getTables($dbName='') {
        $result = $this->query("select table_name from user_tables");
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * SQL指令安全过滤
     * @access public
     * @param string $str  SQL指令
     * @return string
     */
    public function escapeString($str) {
        return str_ireplace("'", "''", $str);
    }

    /**
     * limit
     * @access public
     * @return string
     */
	public function parseLimit($limit) {
        $limitStr    = '';
        if(!empty($limit)) {
            $limit	=	explode(',',$limit);
            if(count($limit)>1)
                $limitStr = "(numrow>" . $limit[0] . ") AND (numrow<=" . ($limit[0]+$limit[1]) . ")";
            else
                $limitStr = "(numrow>0 AND numrow<=".$limit[0].")";
        }
        return $limitStr?' WHERE '.$limitStr:'';
    }

    /**
     * 设置锁机制
     * @access protected
     * @return string
     */
    protected function parseLock($lock=false) {
        if(!$lock) return '';
        return ' FOR UPDATE NOWAIT ';
    }
}
