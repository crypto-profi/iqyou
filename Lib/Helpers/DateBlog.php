<?php

class Zend_View_Helper_DateBlog
{	
	public function dateBlog($mysqlDate, $wordNazad = true, $diff = 0, $getFullDate = false, $withSeconds = false)
	{
    	if (!$mysqlDate && !$diff) {
    	    return '';
    	}
	    
	    if (!$diff) {
    	    $secsNow = time();
    	    $secs = strtotime($mysqlDate);
    	    if (!$secs) {
    	        $secs = time();
    	    }
    	    
            $format = _f('{string} \\� {string}', 'j M', Base_Util_String::getTimeFormat($withSeconds));
            if ($secsNow - $secs > 3 * 86400 || $getFullDate === true) {
                $result = date($format, $secs);
                if (Language_Service_Base::getCurrentLocale() != 'en') {
                    $result = str_replace(array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'), 
                        array(_('���.'), _('���.'), _('���.'), _('���.'), _('���'), _('����'), _('����'), _('���.'), _('����.'), _('���.'), _('����.'), _('���.')), $result);
                }
                return $result;
            }
    	    
    	    $diff = $secsNow - $secs;
	    }
	    if ($diff == 0 || $diff < 0) {
	        return _('������ ���');
	    }
        
        return $this->secToTime($diff, $wordNazad);
	}	
	
	public function secToTime($sec, $wordNazad = false)
	{
        $days = floor($sec / 86400);
    	$hours = floor($sec / 3600);
        $minutes = floor(($sec - ($hours * 3600)) / 60);

	   if ($days) {
            return _f('{plural|%d ����|%d ���|%d ����}{if| �����|}', $days, $wordNazad);
        }
        if ($hours) {
            return _f('{plural|%d ���|%d ����|%d �����}{if| �����|}', $hours, $wordNazad);
        }
        if ($minutes) {
            return _f('{plural|%d ������|%d ������|%d �����}{if| �����|}', $minutes, $wordNazad);
        }
        
        if (!$hours && !$minutes) {
            return $sec . _f(' ���.{if| �����|}', $wordNazad);
        }
        
        return '';
    }

}