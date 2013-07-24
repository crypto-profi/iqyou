<?php

class Base_Service_Profiler_Db extends Zend_Db_Profiler 
{
    private static $instance = null;
    private static $instanceClass = 'Base_Service_Profiler_Db';
    
    /**
     * Creates context singleton. 
     * 
     * @return Base_Service_Profiler_Db
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            $class = self::$instanceClass;
            self::$instance = new $class();
        }
        return self::$instance;
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
                $report .= $this->formatTime($query->getElapsedSecs()) . ' > ' . $query->getQuery();
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