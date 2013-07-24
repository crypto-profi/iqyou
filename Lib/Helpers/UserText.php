<?php

class Zend_View_Helper_UserText
{
	public function userText($text, $limitBreak = 60)
	{
		return nl2br(Utf::wordwrap(strip_tags($text), $limitBreak, ' ', 1));
	}
}