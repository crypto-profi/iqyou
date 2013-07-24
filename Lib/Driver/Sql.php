<?php

class Driver_Sql extends Driver_DBQuery 
{
    public static $dbByTable = array();
    public static $dbnameDefault = '';
    
    /**
     * @return Driver_Select
     */
    public static function select()
    {
    	return new Driver_Select();
    }
    
    public static function qqInsertMulty($binds, &$sqlCols=null)
    {
    	$sqlVals = array();
        foreach ($binds as $bind) {
    	   $sqlVals[] = self::qqInsert($bind, $sqlCols);    
    	}
    	$sql = '('.implode('), (', $sqlVals).')';
    	return $sql;
    }
    
    public static function qqInsert($bind, &$sqlCols=null)
    {
        $cols = array();
        $vals = array();
        foreach ($bind as $col => $val) {
            $cols[] = self::quoteIdentifier($col);
            if ($val instanceof Zend_Db_Expr) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                $vals[] = self::quote($val); // '?'
            }
        }
        
        $sqlCols = implode(', ', $cols);
        $sqlVals = implode(', ', $vals);

        return $sqlVals;
    }
    
    public static function qqUpdate($bind)
    {
    	$set = array();
        foreach ($bind as $col => $val) {
            if ($val instanceof Zend_Db_Expr) {
                $val = $val->__toString();
                unset($bind[$col]);
            } else {
                $val = self::quote($val); // '?';
            }
            $set[] = self::quoteIdentifier($col) . ' = ' . $val;
        }

        $sql = implode(', ', $set);
        return $sql;
    }

    public static function sqlInsert($table, array $bind, $duplicate = '', &$dbName = null, $insertIgnore = false)
    {
        $cols = array();
        $vals = array();

        foreach ($bind as $col => $val) {
            $cols[] = self::quoteIdentifier($col);
            if ($val instanceof Zend_Db_Expr) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                $vals[] = self::quote($val); // '?'
            }
        }

        $sql = 'INSERT '.(!$insertIgnore ? '' : 'IGNORE ').'INTO '
             . self::quoteIdentifier($table)
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES (' . implode(', ', $vals) . ')';

        if (!$insertIgnore && $duplicate) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            if (is_array($duplicate)) {
                $vals = array();
                foreach ($duplicate as $col => $val) {
                    $vals[] = self::quoteIdentifier($col) . ' = ' . self::quote($val);
                }
                $sql .= implode(', ', $vals);
            } else {
                $sql .= $duplicate;
            }
        }

        $dbName = self::_getDbnameByTable($table);     
        return $sql;
    }

    public static function sqlInsertMultiple($table, array $binds, &$dbName = null)
    {
        $cols = array();
        $vals = array();

        foreach ($binds as $k => $bind) {
            foreach ($bind as $col => $val) {
                $cols[] = self::quoteIdentifier($col);

                if ($val instanceof Zend_Db_Expr) {
                    $val = $val->__toString();
                } else {
                    $val = self::quote($val); // '?'
                }

                $binds[$k][$col] = $val;
            }
        }

        $cols = array_unique($cols);

        foreach ($binds as $bind) {
            $sqlVals[] = implode(',', $bind);
        }

        $sql = "INSERT INTO "
             . self::quoteIdentifier($table)
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES ('. implode('), (', $sqlVals) .')';

        $dbName = self::_getDbnameByTable($table);
        return $sql;
    }
    
    public static function sqlInsertMultipleIgnore($table, array $binds, &$dbName = null)
    {
        $cols = array();
        $vals = array();

        foreach ($binds as $k => $bind) {
            foreach ($bind as $col => $val) {
                $cols[] = self::quoteIdentifier($col);

                if ($val instanceof Zend_Db_Expr) {
                    $val = $val->__toString();
                } else {
                    $val = self::quote($val); // '?'
                }

                $binds[$k][$col] = $val;
            }
        }

        $cols = array_unique($cols);

        foreach ($binds as $bind) {
            $sqlVals[] = implode(',', $bind);
        }

        $sql = "INSERT IGNORE INTO "
             . self::quoteIdentifier($table)
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES ('. implode('), (', $sqlVals) .')';

        $dbName = self::_getDbnameByTable($table);
        return $sql;
    }
    
    public static function sqlInsertMultipleUpdate($table, array $binds, $duplicate = false)
    {
        $cols = array();
        $vals = array();

        foreach ($binds as $k => $bind) {
            foreach ($bind as $col => $val) {
                $cols[] = self::quoteIdentifier($col);

                if ($val instanceof Zend_Db_Expr) {
                    $val = $val->__toString();
                } else {
                    $val = self::quote($val); // '?'
                }

                $binds[$k][$col] = $val;
            }
        }

        $cols = array_unique($cols);

        foreach ($binds as $bind) {
            $sqlVals[] = implode(',', $bind);
        }

        $sql = "INSERT IGNORE INTO "
             . self::quoteIdentifier($table)
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES ('. implode('), (', $sqlVals) .')';

        if ($duplicate) {
            $sql .= ' ON DUPLICATE KEY UPDATE ';
            if (is_array($duplicate)) {
                $vals = array();
                foreach ($duplicate as $col => $val) {
                    $vals[] = self::quoteIdentifier($col) . ' = ' . self::quote($val);
                }
                $sql .= implode(', ', $vals);
            } else {
                $sql .= $duplicate;
            }
        }
        
        
        
        $dbName = self::_getDbnameByTable($table);
        return $sql;
    }
    

    public static function sqlUpdate($table, array $bind, $where = '', &$dbName = null)
    {
        $set = array();
        foreach ($bind as $col => $val) {
            if ($val instanceof Zend_Db_Expr) {
                $val = $val->__toString();
                unset($bind[$col]);
            } else {
                $val = self::quote($val); // '?';
            }
            $set[] = self::quoteIdentifier($col) . ' = ' . $val;
        }

        $sql = "UPDATE "
             . self::quoteIdentifier($table)
             . ' SET ' . implode(', ', $set)
             . (($where) ? " WHERE ($where)" : '');

        $dbName = self::_getDbnameByTable($table);     
        return $sql;
    }
    
    public static function sqlDelete($table, $where = '', &$dbName = null)
    {
        $sql = "DELETE FROM "
             . self::quoteIdentifier($table)
             . (($where) ? " WHERE ($where)" : '');
        
        $dbName = self::_getDbnameByTable($table);
        return $sql;     
    }

    public static function sqlReplace($table, array $bind, &$dbName = null)
    {
        $cols = array();
        $vals = array();

        foreach ($bind as $col => $val) {
            $cols[] = self::quoteIdentifier($col);
            if ($val instanceof Zend_Db_Expr) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                $vals[] = self::quote($val); // '?'
            }
        }
        $sql = 'REPLACE INTO '.self::quoteIdentifier($table).' ('.implode(', ', $cols).') VALUES ('.implode(', ', $vals).')';
        $dbName = self::_getDbnameByTable($table);
        return $sql;
    }

    public static function quote($value)
    {
        if (is_int($value)) {
            return $value;
        } elseif (is_float($value)) {
            return sprintf('%F', $value);
        } elseif (is_array($value)) {
            foreach ($value as &$val) {
                $val = self::quote($val);
            }
            return implode(', ', $value);
        } elseif ($value instanceof Zend_Db_Expr) {
            return $value->__toString();
        }
        return "'" . addcslashes($value, "\000\n\r\\'\"\032") . "'";    	
    }
    
    public static function quoteInto($text, $value, $type = null, $count = null)
    {
        if ($count === null) {
            return str_replace('?', self::quote($value, $type), $text);
        } else {
            while ($count > 0) {
                if (Utf::strpos($text, '?') != false) {
                    $text = Utf::substr_replace($text, self::quote($value, $type), Utf::strpos($text, '?'), 1);
                }
                --$count;
            }
            return $text;
        }
    }
    
    public static function quoteIdentifier($value)
    {
    	if (Utf::strpos($value, '*') !== false || Utf::strpos($value, '(') !== false) return $value;
    	if (Utf::strpos($value, '.') !== false) {
    	    $vals = explode('.', $value);
    	    foreach ($vals as &$v) $v = self::quoteIdentifier($v);
    	    return implode('.', $vals);
    	}
        $q = '`';
        return ($q . str_replace("$q", "$q$q", $value) . $q);
    }
    
    public static function qq($subject, $values = '', $identifiers = array())
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        if (!is_array($identifiers)) {
            $identifiers = array($identifiers);
        }

        if (!empty($values)) {
            for ($i = 0; $i < Utf::strlen($subject); $i++) {
                $char = Utf::getChar($subject, $i);
                if ($char == '?' && !empty($values)) {
                    $value = self::quote(array_shift($values));
                    $subject = Utf::substr_replace($subject, $value, $i, 1);
                    $i += Utf::strlen($value);
                }
            }
        }
        
       if (!empty($identifiers)) {
            for ($i = 0; $i < Utf::strlen($subject); $i++) {
                $char = Utf::getChar($subject, $i);
                if ($char == '@' && !empty($identifiers)) {
                    $value = self::quoteIdentifier(array_shift($identifiers));
                    $subject = Utf::substr_replace($subject, $value, $i, 1);
                    $i += Utf::strlen($value);
                }
            }
        }

        return $subject;
    }

    
    /**
     * Следующие две функции занимаются получение имени таблицы из селект
     * и определением dbName по таблице
     */
    public static function _getTableFromSelect($select)
    {
        if (is_object($select)) {
            $select = $select->__toString();
        }
        Utf::preg_match('/FROM\s+`?([\w\d]+)`?/i', $select, $matches);
        if ($matches[1]) {
            return $matches[1];
        }
        else throw new Exception('cant define table ! '.$select);
    }

    public static function _getDbnameByTable($table)
    {
        if (!isset(self::$dbByTable[$table])) {
            $listDb = Base_Application::getInstance()->config['db']['tables'];

            $dbName = 'database';

            // Обработка имени дефолтной базы (сработает только если вдруг это станет не database)
            if (!isset($listDb[$dbName]) || !$listDb[$dbName] == 'DEFAULT') {
                foreach ($listDb as $name => $tables) {
                    if (is_string($tables) && $tables == 'DEFAULT') {
                        $dbName = $name;
                    }
                }
            }

            // Ищем таблицу по полному названию напрямую
            $found = false;
            foreach ($listDb as $name => $tables) {
                if (is_array($tables) && in_array($table, $tables)) {
                    $dbName = $name;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Таблицу не нашли, отрезаем правое число и ищем по названию = префикс*
                if (Utf::preg_match('/((.+)([^(\d+)]))(\d+)$/', $table, $matches)) {
                    $partTable = $matches[1] . '*';
                    foreach ($listDb as $name => $tables) {
                        if (is_array($tables) && in_array($partTable, $tables)) {
                            $dbName = $name;
                            break;
                        }
                    }
                }
            }

            self::$dbByTable[$table] = $dbName;
        }
        return self::$dbByTable[$table];
    }
    
}