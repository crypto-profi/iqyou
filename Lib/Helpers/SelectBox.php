<?php

class Zend_View_Helper_SelectBox
{
	public function selectBox($name, $list, $valuesString, $attrString = '')
    {
    	$result = array();
    	$valuesHash = array(); foreach (explode(',', $valuesString) as $value) $valuesHash[$value] = 1; 
    	$result[] = '<select name="'.$name.'" '.$attrString.'>';
    	foreach ($list as $value => $label) {
    	    $checked = (isset($valuesHash[$value]) ? ' selected="selected"' : '');
    	    $result[] = '<option value="'.$value.'"'.$checked.'>' . $label . '</option>';
    	}
    	$result[] = '</select>';
    	return implode("\n", $result);
    }

}