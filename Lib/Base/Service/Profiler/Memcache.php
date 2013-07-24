<?php

class Base_Service_Profiler_Memcache 
{
    private static $instance = null;

    const CONNECT = 1;
    const GET = 4;
    const SET = 8;
    const DELETE = 16;

    /**
     * @var array
     */
    protected $_queryProfiles = array();

    protected $_enabled = false;

    /**
     * Stores the number of seconds to filter.  NULL if filtering by time is
     * disabled.  If an integer is stored here, profiles whose elapsed time
     * is less than this value in seconds will be unset from
     * the self::$_queryProfiles array.
     *
     * @var integer
     */
    protected $_filterElapsedSecs = null;


    public function __construct($enabled = false)
    {
        $this->setEnabled($enabled);
    }

    /**
     * Creates context singleton. 
     * 
     * @return Base_Service_Profiler_Memcache
     */
    public static function getInstance($enabled = false)
    {
        if (is_null(self::$instance)) {
            self::$instance = new Base_Service_Profiler_Memcache($enabled);
        }
        return self::$instance;
    }    
    
    
    
    /**
     * Enable or disable the profiler.  If $enable is false, the profiler
     * is disabled and will not log any queries sent to it.
     *
     * @param  boolean $enable
     * @return Base_Service_Profiler_Memcache
     */
    public function setEnabled($enable)
    {
        $this->_enabled = (boolean) $enable;

        return $this;
    }

    /**
     * Get the current state of enable.  If True is returned,
     * the profiler is enabled.
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->_enabled;
    }

    /**
     * Sets a minimum number of seconds for saving query profiles.  If this
     * is set, only those queries whose elapsed time is equal or greater than
     * $minimumSeconds will be saved.  To save all queries regardless of
     * elapsed time, set $minimumSeconds to null.
     *
     * @param  integer $minimumSeconds OPTIONAL
     * @return Base_Service_Profiler_Memcache
     */
    public function setFilterElapsedSecs($minimumSeconds = null)
    {
        if (null === $minimumSeconds) {
            $this->_filterElapsedSecs = null;
        } else {
            $this->_filterElapsedSecs = (integer) $minimumSeconds;
        }

        return $this;
    }

    /**
     * Returns the minimum number of seconds for saving query profiles, or null if
     * query profiles are saved regardless of elapsed time.
     *
     * @return integer|null
     */
    public function getFilterElapsedSecs()
    {
        return $this->_filterElapsedSecs;
    }


    /**
     * Clears the history of any past query profiles.  This is relentless
     * and will even clear queries that were started and may not have
     * been marked as ended.
     *
     * @return Base_Service_Profiler_Memcache
     */
    public function clear()
    {
        $this->_queryProfiles = array();

        return $this;
    }

    /**
     * 
     * NADO LI?
     * 
     * @param  integer $queryId
     * @return integer or null
     */
    public function queryClone(Zend_Db_Profiler_Query $query)
    {
        $this->_queryProfiles[] = clone $query;

        end($this->_queryProfiles);

        return key($this->_queryProfiles);
    }

    /**
     * Starts a query.
     * and returns the "query profiler handle".  Run the query, then call
     * queryEnd() and pass it this handle to make the query as ended and
     * record the time.  If the profiler is not enabled, this takes no
     * action and immediately returns null.
     *
     * @param  string  $queryValue   SQL statement
     * @param  integer $queryType   OPTIONAL Type of query
     * @return integer|null
     */
    public function queryStart($queryValue, $queryType = null)
    {
        if (!$this->_enabled) {
            return null;
        }

        // make sure we have a query type
        if (null === $queryType) {
            switch (Utf::strtolower(Utf::substr($queryText, 0, 6))) {
                case 'set':
                    $queryType = self::SET;
                    break;
                case 'delete':
                    $queryType = self::DELETE;
                    break;
                case 'get':
                    $queryType = self::GET;
                    break;
                default:
                    $queryType = self::GET;
                    break;
            }
        }

        $this->_queryProfiles[] = array('value' => $queryValue, 'type' => $queryType, 'startTime' => microtime(true));

        end($this->_queryProfiles);

        return key($this->_queryProfiles);
    }

    /**
     * Ends a query.  Pass it the handle that was returned by queryStart().
     * This will mark the query as ended and save the time.
     *
     * @param  integer $queryId
     * @param  integer $querySize
     * @return void
     */
    public function queryEnd($queryId, $querySize = null)
    {
        // Don't do anything if the profiler is not enabled.
        if (!$this->_enabled) {
            return;
        }

        if (!isset($this->_queryProfiles[$queryId])) {
            /**
             * @see Zend_Exception
             */
            throw new Zend_Exception("Profiler has no query with handle '$queryId'.");
        }

        // End the query profile so that the elapsed time can be calculated.
        $this->_queryProfiles[$queryId]['endTime'] = microtime(true);
        
        if ($querySize) {
            $this->_queryProfiles[$queryId]['size'] = $querySize;
        }

        $currentQueryTime = $this->_queryProfiles[$queryId]['endTime'] - $this->_queryProfiles[$queryId]['startTime'];
        
        /**
         * If filtering by elapsed time is enabled, only keep the profile if
         * it ran for the minimum time.
         */
        if (null !== $this->_filterElapsedSecs && $currentQueryTime < $this->_filterElapsedSecs) {
            unset($this->_queryProfiles[$queryId]);
            return;
        }

    }

    /**
     * Get a profile for a query.  Pass it the same handle that was returned
     * by queryStart()
     *
     * @param  integer $queryId
     * @return array
     */
    public function getQueryProfile($queryId)
    {
        if (!array_key_exists($queryId, $this->_queryProfiles)) {
            throw new Zend_Exception("Query handle '$queryId' not found in profiler log.");
        }

        return $this->_queryProfiles[$queryId];
    }

    /**
     * Get an array of query profiles.  
     *
     * @param  integer $queryType
     * @return array|false
     */
    public function getQueryProfiles($queryType = null)
    {
        $queryProfiles = array();
        foreach ($this->_queryProfiles as $key => $qp) {
            if ($queryType === null) {
                $condition = true;
            } else {
                $condition = ($qp['type'] & $queryType);
            }

            if ($condition) {
                $queryProfiles[$key] = $qp;
            }
        }

        if (empty($queryProfiles)) {
            $queryProfiles = false;
        }

        return $queryProfiles;
    }

    /**
     * Get the total elapsed time (in seconds) of all of the profiled queries.
     * Only queries that have ended will be counted.
     *
     * @param  integer $queryType OPTIONAL
     * @return float
     */
    public function getTotalElapsedSecs($queryType = null)
    {
        $elapsedSecs = 0;
        foreach ($this->_queryProfiles as $key => $qp) {
            if (null === $queryType) {
                $condition = true;
            } else {
                $condition = ($qp['type'] & $queryType);
            }
            if ((isset($qp['endTime'])) && $condition) {
                $elapsedSecs += ($qp['endTime'] - $qp['startTime']);
            }
        }
        return $elapsedSecs;
    }

    /**
     * Get the total number of queries that have been profiled.  Only queries that have ended will
     * be counted.
     *
     * @param  integer $queryType OPTIONAL
     * @return integer
     */
    public function getTotalNumQueries($queryType = null)
    {
        if (null === $queryType) {
            return count($this->_queryProfiles);
        }

        $numQueries = 0;
        foreach ($this->_queryProfiles as $qp) {
            if (isset($qp['endTime']) && ($qp['type'] & $queryType)) {
                $numQueries++;
            }
        }

        return $numQueries;
    }

    /**
     * Get the data for the last query that was run, regardless if it has
     * ended or not.  If the query has not ended, its end time will be null.  If no queries have
     * been profiled, false is returned.
     *
     * @return array|false
     */
    public function getLastQueryProfile()
    {
        if (empty($this->_queryProfiles)) {
            return false;
        }

        end($this->_queryProfiles);

        return current($this->_queryProfiles);
    }
    
    private function getQueryTypeAsString($queryType)
    {
        switch ($queryType) {
                case self::SET:
                    return 'SET';
                    break;
                case self::DELETE:
                    return 'DELETE';
                    break;
                case self::GET:
                    return 'GET';
                    break;
                default:
                    return '';
                    break;
        }
    }
    
    
    public function getHtmlReport()
    {
        $report = '';
        $report .= 'Total queries: <b>' . $this->getTotalNumQueries() . '</b>, time spent: <b>' 
            . $this->formatTime($this->getTotalElapsedSecs(), 1, ' seconds') . '</b> '
            . '(' . $this->formatTime($this->getTotalElapsedSecs()) . ') '
            . "<br/>\n";
        $divId = '';
        if ($this->getQueryProfiles()) {
            foreach ($this->getQueryProfiles() as $query) {
                
                $queryTime = $query['endTime'] - $query['startTime'];
                
                $querySize = isset($query['size']) ? $query['size'] : 0;
                $queryType = $query['type'];
                $queryValue = $querySize > 500 ? Utf::substr($query['value'],0,20) . '...BIGVALUE' : $query['value'];
                
                $report .= $this->formatTime($queryTime) . ' > ' . $this->getQueryTypeAsString($queryType) . ' ' . $queryValue  . ' (' . $querySize . 'b)';
                $report .= "<br />\n";
            }
        }        
        return '<p><small>' . $report . '</small></p>';    	
    }
    
    private function formatTime($float, $factor = 1000, $units = 'ms')
    {
        return  number_format($float * $factor, 3, '.', ' ') . $units;
    }    
    
}