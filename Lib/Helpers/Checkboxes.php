<?php

class Zend_View_Helper_Checkboxes
{	
	public function checkboxes($name, $list, $valuesArray, $params = array()) {
		$result = array();
		$valuesHash = array(); 
		if ($valuesArray) {
		    foreach ($valuesArray as $value) $valuesHash[$value] = 1; 
		}
		
	    foreach ($list as $value => $label) {
		    $checked = (isset($valuesHash[$value]) ? ' checked="true"' : '');
		    $id = 'cb'.rand(1, 999999999);
		    if (@$params['glue'] == 'table') {
		        $result[] = '<tr><td valign="top" style="padding-right: 5px; padding-bottom: 5px;"><input type="checkbox" name="'.$name.'[]" value="'.Base_Util_String::sanitize($value).'" id="' . $id . '"'.$checked.' '.@$params['attr'].'></td><td valign="top" style="padding-bottom: 5px;"><label for="' . $id . '">' . Base_Util_String::usertext($label) . '</label></td>';
		    } else {
	           $result[] = '<input type="checkbox" name="'.$name.'[]" value="'.Base_Util_String::sanitize($value).'" id="' . $id . '"'.$checked.' '.@$params['attr'].'><label for="' . $id . '">' . Base_Util_String::usertext($label) . '</label>';
		    }
		}
		if (@$params['glue'] == 'table') {
		    return '<table cellpadding=0 cellspacing=0 border=0>'.implode("\n", $result).'</table>';
		} else {
            return implode(@$params['glue'], $result);    
		}
	}

}