<?php
/**
 * Created by PhpStorm.
 * User: felixtang
 * Date: 2018/12/25
 * Time: 16:56
 */


class Db_MongoDb {

	private static $ins     = [];
	private $_conn          = null;
	private $_db            = null;

	/**
	 * Updates array.
	 *cou
	 * Public to make debugging easier
	 *
	 * @var array
	 * @access public
	 */
	public $updates = array();

	/**
	 * Wheres array.
	 *
	 * Public to make debugging easier.
	 *
	 * @var array
	 * @access public
	 */
	public $wheres = array();

	/**
	 * Sorts array.
	 *
	 * @var array
	 * @access protected
	 */
	protected $_sorts = array();

	/**
	 * Results limit.
	 *
	 * @var integer
	 * @access protected
	 */
	protected $_limit = 999999;

	/**
	 * Result offset.
	 *
	 * @var integer
	 * @access protected
	 */
	protected $_offset = 0;

	/**
	 * 创建实例
	 * @param string $confkey
	 * @return
	 */
	public static function getInstance($confkey = NULL) {
		$aMongoConf = Yaf_G::getConf('mongodb', []);
		if (!isset($aMongoConf[$confkey])) {
			throw new Exception("Not Found mongo conf" . $confkey);
		}
		if (!isset(self::$ins[$confkey]) && ($conf = $aMongoConf[$confkey])) {
			$mongoDb = new Db_MongoDb($conf);
			self::$ins[$confkey] = $mongoDb;
		}
		return self::$ins[$confkey];
	}


	/**
	 * 构造方法
	 * 单例模式
	 */
	private function __construct(array $conf) {
		$this->_conn = new MongoDB\Driver\Manager($conf["dsn"]. $conf["dbname"], $conf["options"]);
		$this->_db   = $conf["dbname"];
	}

	/*
	/**
	 * 插入数据
	 * @param  string $collname
	 * @param  array  $documents    [["name"=>"values", ...], ...]
	 * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
	 * @return \MongoDB\Driver\Cursor
	 */
	/*function insert($collection_name, array $documents, array $writeOps = []) {
		$cmd = [
			"insert"    => $collection_name,
			"documents" => $documents,
		];
		$cmd += $writeOps;
		return $this->command($cmd);
	}*/


	/**
	 * 删除数据
	 * @param  string $collname
	 * @param  array  $deletes      [["q"=>query,"limit"=>int], ...]
	 * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
	 * @return \MongoDB\Driver\Cursor
	 */
	/*function del($collection_name, array $deletes, array $writeOps = []) {
		foreach($deletes as &$_){
			if (isset($_["q"]) && !$_["q"]){
				$_["q"] = (Object)[];
			}
			if(isset($_["limit"]) && !$_["limit"]){
				$_["limit"] = 0;
			}
		}
		$cmd = [
			"delete"    => $collection_name,
			"deletes"   => $deletes,
		];
		$cmd += $writeOps;
		return $this->command($cmd);
	}*/


	/**
	 * 更新数据
	 * @param  string $collname
	 * @param  array  $updates      [["q"=>query,"u"=>update,"upsert"=>boolean,"multi"=>boolean], ...]
	 * @param  array  $writeOps     ["ordered"=>boolean,"writeConcern"=>array]
	 * @return \MongoDB\Driver\Cursor
	 */
	/*function update($collection_name, array $updates, array $writeOps = []) {
		$cmd = [
			"update"    => $collection_name,
			"updates"   => $updates,
		];
		$cmd += $writeOps;
		return $this->command($cmd);
	}*/


	/**
	 * 查询
	 * @param  string $collname
	 * @param  array  $filter     [query]     参数详情请参见文档。
	 * @return \MongoDB\Driver\Cursor
	 */
	/* function query($collection_name, array $filter, array $writeOps = []){
		 $cmd = [
			 "find"      => $collection_name,
			 "filter"    => $filter,
			 'limit'     => $this->_limit,
			 'skip'      => $this->_offset,
			 'sort'      => $this->_sorts,
		 ];
		 $cmd += $writeOps;
		 return $this->command($cmd);
	 }*/


	/**
	 * 执行MongoDB命令
	 * @param array $param
	 * @return \MongoDB\Driver\Cursor
	 */
	function command(array $param) {
		 $cmd = new MongoDB\Driver\Command($param);
		 return $this->_conn->executeCommand($this->_db, $cmd);
	 }


	/**
	 * 获取当前mongoDB Manager
	 * @return MongoDB\Driver\Manager
	 */
	public function getMongoManager() {
		return $this->_conn;
	}

	public function insert($collection = '', $data = array(), $options = array()) {
		if (empty($collection)) {
			throw new Exception('No Mongo collection selected to insert into');
		}

		if (count($data) === 0 OR ! is_array($data)) {
			throw new Exception('Nothing to insert into Mongo collection or insert is not an array');
		}

		$options = array_merge(array('ordered' => true), $options);

		try {
			$bulkWrite = new\MongoDB\Driver\BulkWrite($options);

			$dbCollectionName = $this->_db . '.' .$collection;

			$result = $bulkWrite->insert($data);

			$this->_conn->executeBulkWrite($dbCollectionName, $bulkWrite);

			$data = [];
			if ($result) {
				$aResult = (array)$result;
				$data['$id'] = $aResult['oid'];
			}
			return $data;

		} catch (Exception $ex) {
			throw new Exception('Insert of data into MongoDB failed: ' . $ex->getMessage());
		}
	}

	// 批量插入
	public function batchInsert($collection = '', $inserts = array(), $options = array()) {
		if (empty($collection)) {
			throw new Exception('No Mongo collection selected to insert into');
		}

		if (count($inserts) === 0 || !is_array($inserts)) {
			throw new Exception('Nothing to insert into Mongo collection or insert is not an array');
		}

		try {

			$bulkWrite = new\MongoDB\Driver\BulkWrite($options);

			$dbCollectionName = $this->_db . '.' .$collection;

			$aIds = [];
			foreach($inserts as $insert) {
				$result = $bulkWrite->insert($insert);
				if ($result) {
					$aResult = (array)$result;
					$aIds[] = ['$id' => $aResult['oid']];
				}
			}
			$this->_conn->executeBulkWrite($dbCollectionName, $bulkWrite);


			return $aIds;

		} catch (Exception $ex) {
			throw new Exception('Insert of data into MongoDB failed: ' . $ex->getMessage());
		}
	}

	public function update($collection = '', $options = array()) {
		if (empty($collection)) {
			throw new Exception('No Mongo collection selected to update');
		}

		if (count($this->updates) === 0) {
			throw new Exception('Nothing to update in Mongo collection or update is not an array');
		}

		try {
			$options = array_merge(array('multi' => false, 'upsert'=>false), $options);

			// $bulkWrite = new\MongoDB\Driver\BulkWrite(['ordered'=>true]);
			$bulkWrite = new\MongoDB\Driver\BulkWrite();
			$writeConcern = new\MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
			$dbCollectionName = $this->_db . '.' . $collection;

			// 格式化id
			$this->formatWhere();

			$bulkWrite->update($this->wheres, $this->updates, $options);

			$oResult = $this->_conn->executeBulkWrite($dbCollectionName, $bulkWrite, $writeConcern);

			$this->_clear($collection, 'update');

			return $oResult;
		} catch (Exception $ex) {
			throw new MongoQB_Exception('Update of data into MongoDB failed: ' . $ex->getMessage());
		}
	}

	// 更新所有
	public function updateAll($collection = '', $options = array()) {
		if (empty($collection)) {
			throw new Exception('No Mongo collection selected to update');
		}

		if (count($this->updates) === 0) {
			throw new Exception('Nothing to update in Mongo collection or  update is not an array');
		}

		try {
			$options = array_merge(array('multi' => true, 'upsert'=>false), $options);

			// $bulkWrite = new\MongoDB\Driver\BulkWrite(['ordered'=>true]);
			$bulkWrite = new\MongoDB\Driver\BulkWrite();

			$writeConcern = new\MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);

			$dbCollectionName = $this->_db . '.' . $collection;

			// 格式化id
			$this->formatWhere();

			$bulkWrite->update($this->wheres, $this->updates, $options);

			$oResult = $this->_conn->executeBulkWrite($dbCollectionName, $bulkWrite, $writeConcern);

			$this->_clear($collection, 'update_all');

			return $oResult;
		} catch (Exception $ex) {
			throw new Exception('Update of data into MongoDB failed: ' . $ex->getMessage());
		}
	}

	// 查询
	public function get($collection = '', $returnCursor = false)
	{
		/*
		$cmd = [
			"find" => $collection,
			"filter" => $this->wheres,
			'limit' => $this->_limit,
			'skip' => $this->_offset,
			'sort' => $this->_sorts,
		];

		$cursor = $this->command($cmd);

		$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

		return $returnCursor? $cursor: $cursor->toArray(); */

		$options = [];

		/*fix by xuchuyuan 20190117
		 * if (!empty($skip)) {
			$options['skip'] = intval($this->_offset);
		}

		if (!empty($limit)) {
			$options['limit'] = intval($this->_limit);
		}

		if (!empty($sort)) {
			$options['sort'] = $sort;
		}*/
        $options['skip'] = intval($this->_offset);
        $options['limit'] = intval($this->_limit);

		// 格式化id
		$this->formatWhere();

		$query = new\MongoDB\Driver\Query($this->wheres, $options);
		$cursor = $this->_conn->executeQuery($this->_db. '.' .$collection, $query);
		$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);

		$this->_clear($collection, 'get');

		return $returnCursor? $cursor: $cursor->toArray();
	}

    /**
     * count
     * @param string $collection
     * @return int
     * @throws \MongoDB\Driver\Exception\Exception
     */
	public function count($collection = '') {
		if (empty($collection)) {
			throw new Exception('In order to retrieve a count of documents from MongoDB, a collection name must be passed');
		}

		$this->formatWhere();

		/*rewrite by xuchuyuan*/
        $cmd = array(
            'count'=> $collection,
            'query'=> $this->wheres
        );
        $command = new\MongoDB\Driver\Command($cmd);
        $result = $this->_conn->executeCommand($this->_db, $command);
        $response= current($result->toArray());
        if($response->ok == 1){
            return $response->n;
        }

        return 0;

        /* destory by xuchuyuan at 3.11 15:00
        $query = new\MongoDB\Driver\Query($this->wheres, []);
		$cursor = $this->_conn->executeQuery($this->_db. '.' .$collection, $query);
		$cursor->setTypeMap(['root' => 'array', 'document' => 'array', 'array' => 'array']);
		$this->_clear($collection, 'count');
		return count($cursor->toArray());
        */
	}

	// 删除
	public function delete($collection = '', $options=[]) {
		if (empty($collection)) {
			throw new Exception('No Mongo collection selected to delete from');
		}

		try {
			$bulkWrite = new\MongoDB\Driver\BulkWrite(['ordered'=>true]);
			$writeConcern = new\MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
			$dbCollectionName = $this->_db . '.' . $collection;

			$bulkWrite->delete($this->wheres, $options);

			$this->_conn->executeBulkWrite($dbCollectionName, $bulkWrite, $writeConcern);

			$this->_clear($collection, 'delete');

			return true;
		} catch (Exception $ex) {
			throw new MongoQB_Exception('Delete of data into MongoDB failed: ' . $ex->getMessage());
		}
	}


	// 格式化where
	public function formatWhere() {
		if (isset($this->wheres['_id'])) {
			if (is_array($this->wheres['_id'])) {
				$arr = [];
				foreach ($this->wheres['_id'] as $key => $val) {
					$arr[$key] = new \MongoDB\BSON\ObjectId($val);
				}
			} else {
				$this->wheres['_id'] = new \MongoDB\BSON\ObjectId($this->wheres['_id']);
			}
		}
	}

	/**
	 * Set where paramaters
	 *
	 * Get the documents based on these search parameters.  The $wheres array
	 *  should be an associative array with the field as the key and the value
	 *  as the search criteria.
	 *
	 * @param array|string $wheres Array of where conditions. If string, $value
	 *  must be set
	 * @param mixed $value Value of $wheres if $wheres is a string
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function where($wheres = array(), $value = null) {
		if (is_array($wheres)) {
			foreach ($wheres as $where => $value) {
				$this->wheres[$where] = $value;
			}
		} else {
			$this->wheres[$wheres] = $value;
		}

		return $this;
	}

	/**
	 * Set.
	 *
	 * Sets a field to a value
	 *
	 * @param array|string $fields Array of field names (or a single string
	 *  field name)
	 * @param mixed $value Value that the field(s) should be set to
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function set($fields, $value = null) {
		$this->_updateInit('$set');

		if (is_string($fields)) {
			$this->updates['$set'][$fields] = $value;
		} elseif (is_array($fields)) {
			foreach ($fields as $field => $value) {
				$this->updates['$set'][$field] = $value;
			}
		}

		return $this;
	}


	/**
	 * or_where.
	 *
	 * Get the documents where the value of a $field may be something else
	 *
	 * @param array $wheres Array of where conditions
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function orWhere($wheres = array()) {
		if (count($wheres) > 0) {
			if (!isset($this->wheres['$or']) OR !
				is_array($this->wheres['$or'])) {
				$this->wheres['$or'] = array();
			}

			foreach ($wheres as $where => $value) {
				$this->wheres['$or'][] = array($where => $value);
			}
		}

		return $this;
	}

	/**
	 * Where in array.
	 *
	 * Get the documents where the value of a $field is in a given $in array().
	 *
	 * @param string $field	 Name of the field
	 * @param array  $inValues Array of values that $field could be
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereIn($field = '', $inValues = array()) {
		$this->_whereInit($field);
		$this->wheres[$field]['$in'] = $inValues;

		return $this;
	}

	/**
	 * Where all are in array.
	 *
	 * Get the documents where the value of a $field is in all of a given $in
	 *  array().
	 *
	 * @param string $field	 Name of the field
	 * @param array  $inValues Array of values that $field must be
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereInAll($field = '', $inValues = array()) {
		$this->_whereInit($field);
		$this->wheres[$field]['$all'] = $inValues;

		return $this;
	}

	/**
	 * Where not in
	 *
	 * Get the documents where the value of a $field is not in a given $in
	 *  array().
	 *
	 * @param string $field	 Name of the field
	 * @param array  $inValues Array of values that $field isnt
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereNotIn($field = '', $inValues = array()) {
		$this->_whereInit($field);
		$this->wheres[$field]['$nin'] = $inValues;

		return $this;
	}

	/**
	 * Where greater than
	 *
	 * Get the documents where the value of a $field is greater than $value.
	 *
	 * @param string $field Name of the field
	 * @param mixed  $value Value that $field is greater than
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereGt($field = '', $value = null) {
		$this->_whereInit($field);
		$this->wheres[$field]['$gt'] = $value;

		return $this;
	}

	/**
	 * Where greater than or equal to
	 *
	 * Get the documents where the value of a $field is greater than or equal to
	 *  $value.
	 *
	 * @param string $field Name of the field
	 * @param mixed  $value Value that $field is greater than or equal to
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereGte($field = '', $value = null) {
		$this->_whereInit($field);
		$this->wheres[$field]['$gte'] = $value;

		return $this;
	}

	/**
	 * Where less than.
	 *
	 * Get the documents where the value of a $field is less than $x
	 *
	 * @param string $field Name of the field
	 * @param mixed  $value Value that $field is less than
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereLt($field = '', $value = null) {
		$this->_whereInit($field);
		$this->wheres[$field]['$lt'] = $value;

		return $this;
	}

	/**
	 * Where less than or equal to
	 *
	 * Get the documents where the value of a $field is less than or equal to $x
	 *
	 * @param string $field Name of the field
	 * @param mixed  $value Value that $field is less than or equal to
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereLte($field = '', $value = null) {
		$this->_whereInit($field);
		$this->wheres[$field]['$lte'] = $value;

		return $this;
	}

	/**
	 * Where between two values
	 *
	 * Get the documents where the value of a $field is between $x and $y
	 *
	 * @param string $field   Name of the field
	 * @param int	$valueX Value that $field is greater than or equal to
	 * @param int	$valueY Value that $field is less than or equal to
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereBetween($field = '', $valueX = 0, $valueY = 0) {
		$this->_whereInit($field);
		$this->wheres[$field]['$gte'] = $valueX;
		$this->wheres[$field]['$lte'] = $valueY;

		return $this;
	}

	/**
	 * Where between two values but not equal to
	 *
	 * Get the documents where the value of a $field is between but not equal to
	 *  $x and $y
	 *
	 * @param string $field   Name of the field
	 * @param int	$valueX Value that $field is greater than or equal to
	 * @param int	$valueY Value that $field is less than or equal to
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereBetweenNe($field = '', $valueX, $valueY) {
		$this->_whereInit($field);
		$this->wheres[$field]['$gt'] = $valueX;
		$this->wheres[$field]['$lt'] = $valueY;

		return $this;
	}

	/**
	 * Where not equal to
	 *
	 * Get the documents where the value of a $field is not equal to $x
	 *
	 * @param string $field Name of the field
	 * @param mixed  $value Value that $field is not equal to
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereNe($field = '', $value) {
		$this->_whereInit($field);
		$this->wheres[$field]['$ne'] = $value;

		return $this;
	}

	/**
	 * Where near
	 *
	 * Get the documents nearest to an array of coordinates (your collection
	 *  must have a geospatial index)
	 *
	 * @param string  $field	 Name of the field
	 * @param array   $coords	Array of coordinates
	 * @param integer $distance  Value of the maximum distance to search
	 * @param boolean $spherical Treat the Earth as spherical instead of flat
	 *  (useful when searching over large distances)
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function whereNear($field = '', $coords = array(), $distance = null, $spherical = false) {
		$this->_whereInit($field);

		if ($spherical) {
			$this->wheres[$field]['$nearSphere'] = $coords;
		} else {
			$this->wheres[$field]['$near'] = $coords;
		}

		if ($distance !== null) {
			$this->wheres[$field]['$maxDistance'] = $distance;
		}

		return $this;
	}



	/**
	 * Order results by
	 *
	 * Sort the documents based on the parameters passed. To set values to
	 *  descending order, you must pass values of either -1, false, 'desc', or
	 *  'DESC', else they will be set to 1 (ASC).
	 *
	 * @param array $fields Array of fields with their sort type (asc or desc)
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function orderBy($fields = array()) {
		foreach ($fields as $field => $order) {
			if ($order === -1 OR $order === false OR strtolower($order) === 'desc') {
				$this->_sorts[$field] = -1;
			} else {
				$this->_sorts[$field] = 1;
			}
		}

		return $this;
	}

	/**
	 * Limit the number of results
	 *
	 * Limit the result set to $limit number of documents
	 *
	 * @param int $limit The maximum number of documents that will be returned
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function limit($limit = 99999) {
		if ($limit !== null AND is_numeric($limit) AND $limit >= 1) {
			$this->_limit = (int) $limit;
		}

		return $this;
	}

	/**
	 * Offset results
	 *
	 * Offset the result set to skip $x number of documents
	 *
	 * @param int $offset The number of documents to offset the search by
	 *
	 * @access public
	 * @return MongoQB_Builder
	 */
	public function offset($offset = 0) {
		if ($offset !== null AND is_numeric($offset) AND $offset >= 1) {
			$this->_offset = (int) $offset;
		}

		return $this;
	}

	/**
	 * Get where.
	 *
	 * Get the documents based upon the passed parameters
	 *
	 * @param string $collection Name of the collection
	 * @param array  $where	  Array of where conditions
	 *
	 * @access public
	 * @return array
	 */
	public function getWhere($collection = '', $where = array()) {
		return $this->where($where)->get($collection);
	}


	private function _whereInit($field) {
		if (!isset($this->wheres[$field])) {
			$this->wheres[$field] = array();
		}
	}

	/**
	 * Update initializer.
	 *
	 * Prepares parameters for insertion in $updates array().
	 *
	 * @param string $field Field name
	 *
	 * @access private
	 * @return void
	 */
	private function _updateInit($field = '') {
		if (!isset($this->updates[$field])) {
			$this->updates[$field] = array();
		}
	}

	private function _clear($collection, $action) {
		$this->updates = array();
		$this->wheres = array();
		$this->_limit = 999999;
		$this->_offset = 0;
		$this->_sorts = array();
	}

    /**
     * 模糊匹配
     * @param $value
     * @param $regRule
     * @param string $flags
     */
    public static function _buildLikeValue($value, $regRule, $flags = 'i')
    {
        switch ($regRule) {
            case 'like':
                return new \MongoDB\BSON\Regex($value, $flags);
                break;
            case 'leftlike':
                return new \MongoDB\BSON\Regex("^" . $value, $flags);
                break;
            case 'rightlike':
                return new \MongoDB\BSON\Regex($value . "$", $flags);
                break;
        }
    }

    /**
     * 聚合查询
     * @param $collName
     * @param array $where
     * @param array $group
     * @return \MongoDB\Driver\Cursor
     */
    function aggregate($collName, array $where, array $group)
    {
        $res = [];
        $cmd = [
            'aggregate' => $collName,
            'pipeline' => [
                [ '$match' => $where],
                [ '$group' => $group]
            ],
            'cursor' => new stdClass(),
        ];
        $result = $this->command($cmd)->toArray();
        foreach ($result as $r) {
            $res[] = (array)$r;
        }

        return $res;
    }
}