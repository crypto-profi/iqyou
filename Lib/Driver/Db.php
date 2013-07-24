<?php

class Driver_Db extends Driver_Sql 
{
    /* апдейты */
    
    /**
     *
     * @param string $table
     * @param string $query
     * @param type $_method
     * @return type 
     */
    public static function writequery($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return self::queryWrite($query, $dbName, $_method, '', $table);
    }
    
    /**
     *
     * @param string $table
     * @param array $bind
     * @param type $_method
     * @param type $duplicate
     * @return type 
     */
    public static function insert($table, array $bind, $_method = '', $duplicate = '')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlInsert($table, $bind, $duplicate, $dbName);
        return parent::insert($query, $dbName, $_method, '', $table);
    }
    
    public static function insertIgnore($table, array $bind, $_method = '')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlInsert($table, $bind, '', $dbName, true);
        return parent::insert($query, $dbName, $_method, '', $table);
    }

    /**
     * Вставка нескольких строк в таблицу, порядок ключей и количество во всех foreach ($bindArray as $bind) должны быть одинаковыми
     *
     * @param [array] $bindArray - это массив $bind как в insert
     *
     */
    public static function insertMultiple($table, array $bindArray, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlInsertMultiple($table, $bindArray, $dbName);
        return parent::insert($query, $dbName, $_method, '', $table);
    }
    
    public static function insertMultiplePacks($table, array $bindArrays, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query  = null;
        $result = null;
        
        foreach ($bindArrays as $bindArray) {
            $query .= self::sqlInsertMultiple($table, $bindArray, $dbName) . ';';
        }
        
        if ($query) {
            $result = parent::insert($query, $dbName, $_method, '', $table);
        }
        
        return $result;
    }
    
    public static function insertMultipleUpdatePacks($table, array $bindArrays, $duplicate = false, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query  = null;
        $result = null;
        
        foreach ($bindArrays as $bindArray) {
            $query .= self::sqlInsertMultipleUpdate($table, $bindArray, $duplicate, $_method) . ';';
        }
        
        if ($query) {
            $result = parent::insert($query, $dbName, $_method, '', $table);
        }
        
        return $result;
    }
    
    
    /**
     * Вставка нескольких строк в таблицу, с игнорирование дубликаций
     * порядок ключей и количество во всех foreach ($bindArray as $bind) должны быть одинаковыми
     *
     * @param        $table
     * @param array  $bindArray это массив $bind как в insert
     * @param string $_method
     *
     * @return int
     */
    public static function insertMultipleIgnore($table, array $bindArray, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlInsertMultipleIgnore($table, $bindArray, $dbName);
        return parent::insert($query, $dbName, $_method, '', $table);
    }
    
    /**
     * Вставка нескольких строк в таблицу, с игнорирование дубликаций
     * порядок ключей и количество во всех foreach ($bindArray as $bind) должны быть одинаковыми
     *
     * @param        $table
     * @param array  $bindArray это массив $bind как в insert
     * @param bool|array|string   $duplicate
     * @param string $_method
     *
     * @return int
     */
    public static function insertMultipleUpdate($table, array $bindArray, $duplicate = false, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlInsertMultipleUpdate($table, $bindArray, $duplicate);
        return parent::insert($query, $dbName, $_method, '', $table);
    }

    /**
     * Вставка вместе с блоком ON DUPLICATE UPDATE
     *
     * @param        $table
     * @param array  $bind
     * @param string $duplicate string - вставляется в конец "как есть", array - работает как $bind
     * @param string $_method
     *
     * @return int
     */
    public static function insertd($table, array $bind, $duplicate = '', $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlInsert($table, $bind, $duplicate, $dbName);
        return parent::insert($query, $dbName, $_method, '', $table);
    }

    public static function update($table, array $bind, $where = '', $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlUpdate($table, $bind, $where, $dbName);
        return self::queryWrite($query, $dbName, $_method, '', $table);
    }

    public static function delete($table, $where = '', $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlDelete($table, $where, $dbName);
        return self::queryWrite($query, $dbName, $_method, '', $table);
    }

    public static function replace($table, array $bind, $_method = '')
    {
        $dbName = self::_getDbnameByTable($table);
        $query = self::sqlReplace($table, $bind, $dbName);
        return self::queryWrite($query, $dbName, $_method, '', $table);
    }

    /* селекты */
    
    private static function _fetchAbstract($function, $select, $dbName='', $_method='')
    {
        if (is_object($select)) {
            $query = $select->__toString();
            // тут добавляем имя группы баз
            $dbName = Driver_Sql::_getDbnameByTable(Driver_Sql::_getTableFromSelect($query));
        } else {
            $query = $select;
            if (!$dbName) {
                $dbName = self::_getDbnameByTable(self::_getTableFromSelect($select));
            }
        }
        return parent::$function($query, $dbName, $_method);
    }
    
    public static function selectAll($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return parent::fetchAll($query, $dbName, $_method);
    }

    public static function selectOne($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return parent::fetchOne($query, $dbName, $_method);
    }

    public static function selectRow($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return parent::fetchRow($query, $dbName, $_method);
    }
    
    public static function selectAllMaster($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return parent::fetchAllMaster($query, $dbName, $_method);
    }

    public static function selectOneMaster($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return parent::fetchOneMaster($query, $dbName, $_method);
    }
    
    public static function selectRowMaster($table, $query, $_method='')
    {
        $dbName = self::_getDbnameByTable($table);
        return parent::fetchRowMaster($query, $dbName, $_method);
    }
    
    public static function fetchCol($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchAll($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchOne($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchRow($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchPairs($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchAssoc($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchColMaster($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchAllMaster($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchOneMaster($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchRowMaster($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchPairsMaster($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }

    public static function fetchPairsSlave($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchAssocMaster($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }

    public static function fetchAssocSlave($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchColSlave($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
    public static function fetchAllSlave($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }

    /**
     * @param Driver_Select|string $select объект запроса или просто строка sql
     * @param string $_method   метод из которого вызван запрос
     * @param string $dbName    БД в которую пойдет запрос
     * @return array выборка из базы данных
     *
     * Делает селект со слейва БД игнорируя то, что слейв выключен
     * Использование должно проходить аппрув тех. группы
     * Ведется (скрытое видео-...) логирование
     */
    public static function fetchAllSlaveSurely($select, $_method='', $dbName='')
    {
        return self::_fetchAbstract(__FUNCTION__, $select, $dbName, $_method);
    }
    
}
