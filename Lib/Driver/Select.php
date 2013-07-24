<?php

class Driver_Select
{
    public $string = array();
    
    /**
     * Magic method - executes on echo object of class
     */
    public function __toString()
    {
        $res = "SELECT " 
            . (@$this->string['distinct'] ? $this->string['distinct'].' ' : '') 
            . implode(', ', $this->string['columns']) 
            . " FROM " . $this->_tableQuoted($this->string['from']['name']);

        if (@$this->string['use_index']) {
            $res .= ' USE INDEX(`' . $this->string['use_index'] . '`)';
        }

        if (@$this->string['force_index']) {
            $res .= ' FORCE INDEX(`' . $this->string['force_index'] . '`)';
        }

    	if (@$this->string['join']) {
    	   $res .= "\n " . implode("\n ", $this->string['join']);
    	}
        if (@$this->string['where']) {
    	   $res .= " " . 'WHERE ('.implode(') AND (', $this->string['where']).')';
    	}
    	if (@$this->string['group']) {
    	   $res .= " " . $this->string['group'];
    	}
        if (@$this->string['having']) {
            $res .= " HAVING " . $this->string['having'];
        }
    	if (@$this->string['order']) {
    	   $res .= " ORDER BY " . implode(', ', $this->string['order']);
    	}
    	if (@$this->string['limit']) {
    	   $res .= " " . $this->string['limit'];    
    	}
    	
    	return $res;
    }

    /**
     * @return Driver_Select
     */
    public function having($having)
    {
        $this->string['having'] = $having;
        return $this;
    }
    
    /**
     * @return Driver_Select
     */
    public function limit($count, $offset=0)
    {
        $this->string['limit'] = "LIMIT $count".($offset ? ' OFFSET '.$offset : '');
        return $this;
    }
    
    /**
     * @return Driver_Select
     */
    public function limitPage($page, $rowCount)
    {
        $page     = ($page > 0)     ? $page     : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        return $this->limit((int) $rowCount, (int) $rowCount * ($page - 1));
    }
    
    /**
     * @return Driver_Select
     */
    public function order($spec)
    {
        if (is_array($spec)) {
            foreach ($spec as $i => &$v) $v = Driver_Sql::quoteIdentifier($v); 
            $spec = implode(', ', $spec);
        } else {
            $spec = Driver_Sql::quoteIdentifier($spec);            
        }
        $spec = str_ireplace(array(' ASC`', ' DESC`'), array('` ASC', '` DESC'), $spec);
        if (!Utf::stripos($spec, 'ASC') && !Utf::stripos($spec, 'DESC')) $spec .= ' ASC';
        
        @$this->string['order'][] = $spec;
        return $this;
    }
    
    /**
     * @return Driver_Select
     */
    public function group($spec)
    {
        if (is_array($spec)) {
            foreach ($spec as $i => &$v) $v = Driver_Sql::quoteIdentifier($v); 
            $spec = implode(', ', $spec);
        } else {
            $spec = Driver_Sql::quoteIdentifier($spec);            
        }
        
        $this->string['group'] = "GROUP BY $spec";
        return $this;
    }

    /**
     * @return Driver_Select
     */
    public function useIndex($indexName) {
        $this->string['use_index'] = $indexName;

        return $this;
    }

    /**
     * @return Driver_Select
     */
    public function forceIndex($indexName) {
        $this->string['force_index'] = $indexName;

        return $this;
    }
    
    /**
     * @return Driver_Select
     */
    public function from($name, $cols = array('*'))
    {
        @$this->string['from']['name'] = $name;
        $this->columns($cols, $name);
        return $this;
    }
    
    public function distinct($flag = true)
    {
        @$this->string['distinct'] = 'DISTINCT';
        return $this;
    }    
    
    public function reset($target)
    {
    	if ($target == 'columns') {
    	    $this->string['columns'] = array(); 
    	} elseif ($target == 'limitcount' || $target == 'limitoffset') {
    	    $this->string['limit'] = '';
    	}
    	return $this;
    }
    
    private function _tableQuoted($name)
    {
    	if (is_array($name)) {
            foreach ($name as $k => $v) $tableSql = Driver_Sql::quoteIdentifier($v)." AS ".Driver_Sql::quoteIdentifier($k);
        } else {
            $tableSql = Driver_Sql::quoteIdentifier($name);
        }
        return $tableSql;
    }
    
    /**
     * @return Driver_Select
     */
    public function columns($cols, $table = null)
    {
        if (is_array($table)) {
            $_table = ''; foreach ($table as $k => $v) $_table = $k;
            $table = $_table;
        }
        if ($cols) {
            if (!is_array($cols)) $cols = array($cols);
            foreach ($cols as $ascol => $expr) {
                // условия если ассоц. массив, выражения дб-ехпр, и не выражение просто типа count(*)
                if (is_int($ascol)) {
                    if ($expr instanceof Zend_Db_Expr) {
                        $col = $expr->__toString();    
                    } else {
                        $col = ($table && !Utf::strpos($expr, '(') && !Utf::strpos($expr, '.') ? Driver_Sql::quoteIdentifier($table)."." : '') 
                            . Driver_Sql::quoteIdentifier($expr);                  
                    }
                } else {
                    $col = ($table && !Utf::strpos($expr, '(') && !Utf::strpos($expr, '.') ? Driver_Sql::quoteIdentifier($table).".".Driver_Sql::quoteIdentifier($expr) : $expr) 
                        . " AS ".Driver_Sql::quoteIdentifier($ascol);
                }
                @$this->string['columns'][] = $col;
            }
        }
        return $this;
    }
    
    /**
     * @return Driver_Select
     */
    private function _join($type, $name, $cond, $cols = array('*'))
    {
        $tableSql = $this->_tableQuoted($name);
        
        if ($type == 'left') $join = "LEFT JOIN";
        elseif ($type == 'inner') $join = "INNER JOIN";
        
        @$this->string['join'][] = "$join $tableSql ON $cond";
        $this->columns($cols, $name);
        return $this;
    }
    
    /**
     * @return Driver_Select
     */
    public function joinLeft($name, $cond, $cols = array('*'))
    {
        return $this->_join('left', $name, $cond, $cols);
    }
    
    /**
     * @return Driver_Select
     */
    public function join($name, $cond, $cols = array('*'))
    {
        return $this->_join('inner', $name, $cond, $cols);
    }

    /**
     * @return Driver_Select
     */
    public function joinInner($name, $cond, $cols = array('*'))
    {
        return $this->_join('inner', $name, $cond, $cols);
    }
    
    /**
     * @return Driver_Select
     */
    public function where($cond, $value=null)
    {
        if (is_array($value)) {
            foreach ($value as $i => &$v) $v = Driver_Sql::quote($v); 
            $value = implode(', ', $value);
        } elseif ($value instanceof Zend_Db_Expr) {
            $value = $value->__toString();
        } else {
            $value = Driver_Sql::quote($value);            
        }
        $cond = str_replace('?', $value, $cond);
        @$this->string['where'][] = $cond;
        return $this;
    }
    
}
    
