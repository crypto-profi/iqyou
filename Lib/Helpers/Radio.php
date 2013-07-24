<?php 

class Zend_View_Helper_Radio
{	
	public function radio($name, $list, $valuesArray, $params = array()) {
		$result = array();
		$valuesHash = array(); 
		if ($valuesArray) {
		    foreach ($valuesArray as $value) $valuesHash[$value] = 1; 
		}
	    foreach ($list as $value => $label) {
		    $id = 'cb' . $name . rand(1, 999999999);
		    $checked = (isset($valuesHash[$value]) ? ' checked="true"' : '');
		    
		    $js = '';
		    if(isset($params['js'])){
		    	$js = $params['js'];
		    }
		    
		    if (@$params['glue'] == 'table') {
		        $result[] = '<tr><td valign="top" style="padding-right: 5px; padding-bottom: 5px;"><input type="radio" '.$js.' name="'.$name.'" value="'.Base_Util_String::sanitize($value).'" id="' . $id . '"'.$checked.' '.@$params['attr'].'></td><td valign="top" style="padding-bottom: 5px;"><label for="' . $id . '">' . Base_Util_String::usertext($label) . '</label></td></tr>';
		    } else {
                $result[] = '<input type="radio" name="'.$name.'" value="'.Base_Util_String::sanitize($value).'" id="' . $id . '"'.$checked.' '.@$params['attr'].'><label for="' . $id . '">' . Base_Util_String::usertext($label) . '</label>';		        
		    }
		}
		if (@$params['glue'] == 'table') {
		    return '<table cellpadding=0 cellspacing=0 border=0>'.implode("\n", $result).'</table>';
		} else {
            return implode(@$params['glue'], $result);    
		}
	}
	
	

}