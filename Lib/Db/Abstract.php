<?php

class Db_Abstract
{
	/**
     * @var Driver_Db
     */
    public $db;

    /**
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    public $dbStats;

    public $table;

	public function __construct()
	{
		$this->db = Base_Context::getInstance()->getDbConnection();
	    $this->dbStats = new Driver_Db();
	}

    public static function now()
    {
    	return date('Y-m-d H:i:s');
    }

    /**
     *
     * @deprecated use Driver_Db::qq()
     */
	public function qq($subject, $values = '', $identifiers = array())
	{
        return Driver_Db::qq($subject, $values, $identifiers);
	}

	public function getAll($where = '', $order = '')
	{
		$select = $this->db->select()->from($this->table);
		if ($where) $select->where($where);
		if ($order) $select->order($order);
		return $this->db->fetchAll($select, __METHOD__.'.'.$this->table);
	}

	public function getById($id)
	{
		if (!$id) return array();
	    $select = $this->db->select()->from($this->table)->where("{$this->table}_id = ?", $id);
	    return $this->db->fetchRow($select, __METHOD__.'.'.$this->table);
	}

	public function getByField($id, $field)
	{
		$select = $this->db->select()->from($this->table)->where("{$this->table}_$field = ?", $id);
	    return $this->db->fetchRow($select, __METHOD__.'.'.$this->table);
	}

	public function getByFd($id, $field)
	{
		$select = $this->db->select()->from($this->table)->where("$field = ?", $id);
	    return $this->db->fetchRow($select, __METHOD__.'.'.$this->table);
	}

	public function getCount()
	{
		$select = $this->db->select()->from($this->table, array('COUNT(*)'));
		$count = $this->db->fetchOne($select, __METHOD__.'.'.$this->table);
		return $count ? $count : 0;
	}

	public function getMaxId()
	{
		$select = $this->db->select()->from($this->table, array("MAX({$this->table}_id)"));
		$max = $this->db->fetchOne($select, __METHOD__.'.'.$this->table);
		return $max ? $max : 0;
	}

    /**
	 * Making pager easier
	 */
	public function fetchPaging($select, $pagerLimit, $currentPage, $fetchType = 'all', $countRecs = null, $_method='')
	{
		$result = new stdClass();
		if ($countRecs) {
			$select = $this->applyLimit($select, $pagerLimit, $currentPage);
		} else {
			$select = $this->patchSelectForPager($select, $pagerLimit, $currentPage);
		}

		if ($fetchType == 'all') {
			$result->recs = $this->db->fetchAll($select, $_method);
		} elseif ($fetchType == 'col') {
			$result->recs = $this->db->fetchCol($select, $_method);
		}


		$selectInfo = $this->getSelectInfo($select, $pagerLimit, $countRecs);

		$result->count = $selectInfo->count;
		$result->pages = $selectInfo->pages;

		return $result;
		return array();
	}

    private function patchSelectForPager($select, $pager_limit, $pager_current)
	{
	    $select = $this->applyLimit($select, $pager_limit, $pager_current);
        // @bad - но в объекте select нет такой команды
        //$select = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $select->__toString());
        return $select;
	}

	private function applyLimit($select, $pager_limit, $pager_current)
	{
		if ($pager_current <= 0) {
			$pager_current = 1;
		}

        if ($pager_limit) {

            if (is_object($select)) {
        	   $select->limit($pager_limit, ($pager_current - 1) * $pager_limit);
            } else {
                $offset = ($pager_current - 1) * $pager_limit;
                $select = Utf::preg_replace('/LIMIT .*$/', '', $select);
                $select .= " LIMIT $pager_limit ".($offset ? "OFFSET $offset" : '');
            }
		}
		return $select;
	}

	public function getSelectInfo($selectObj, $pager_limit, $count = null)
	{
		$select = new stdClass();

		if (is_object($selectObj)) {
    		$selectCount = clone $selectObj;

    		$selectCount->reset('limitcount')->reset('limitoffset')->reset('columns');
    		$selectCount->columns('COUNT(*)');
		} else {
		    $selectCount = Utf::preg_replace('/LIMIT .*$/', '', $selectObj);
		    $selectCount = Utf::preg_replace('/^SELECT .*? FROM/', 'SELECT COUNT(*) FROM', $selectCount);
		}
		$select->count = ($count ? $count : $this->db->fetchOne($selectCount, __METHOD__));
		if (!$pager_limit) {
			$select->pages = 0;
		} else {
			$select->pages = ceil($select->count / $pager_limit);
		}

		return $select;
	}
	
    public function getFoundRowsSelect1($selectObj, $pagerLimit, $offPage, $checkPage = 16)
    {
        $select = new stdClass();
        if($offPage) $offPage--;

        if (is_object($selectObj)) {
            $selectCount = $selectObj->__toString();;
        }
        $selectCount = Utf::preg_replace('/LIMIT .*$/', 'LIMIT '.$pagerLimit*$checkPage.' OFFSET '.$pagerLimit*$offPage, $selectObj);
        $selectCount = Utf::preg_replace('/^SELECT .*? FROM/', 'SELECT 1 FROM', $selectCount);
        $selectCount = Utf::preg_replace('/ORDER BY .*? ASC/', '', $selectCount);
        $selectCount = Utf::preg_replace('/ORDER BY .*? DESC/', '', $selectCount);
        $selectCount = Utf::preg_replace('/ORDER BY .*?/', '', $selectCount);

        $select->count = $offPage*$pagerLimit + count($this->db->fetchAll($selectCount, __METHOD__));
        if (!$pagerLimit) {
            $select->pages = 0;
        } else {
            $select->pages = ceil($select->count / $pagerLimit);
        }
        return $select;
    }	
}