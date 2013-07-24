<?php

class Zend_View_Helper_Date
{	
	/**
	 * ��������� �����, �.�. ������� ��� xml ������... � ��� ��� ... �)
	 */
	public function date($time, $format='')
	{
        if (!$time) $time = time();
	    if (!is_numeric($time)) {
            $time = strtotime($time);
        }
        if ($format == 'EE') {
        	$result = date('D', $time);
	        $result = str_replace(
	        	array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'),
	        	array('��', '��', '��', '��', '��', '��', '��'),
	        	$result
	        );
	        return $result;
        }
        
        $format = 'j M H:i';
        $result = date($format, $time);
        $result = str_replace(array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'), 
            array('���.', '���.', '���.', '���.', '���', '����', '����', '���.', '����.', '���.', '����.', '���.'), $result);
        return $result;
	}	
}