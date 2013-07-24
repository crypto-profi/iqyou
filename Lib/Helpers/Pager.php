<?php

class Zend_View_Helper_Pager
{	
	private $url;
	private $pagesCount;
	private $currentPage;
	private $splitter;
	private $limitAllPages;
	
	public function pager($url, $pagesCount, $currentPage, $params = array())
	{
		$this->url = (Utf::strpos($url,'?'))? $url.'&' : $url.'?';
		$this->pagesCount = $pagesCount;
		if (!$this->pagesCount) return '';
		
		$this->currentPage = ($currentPage ? $currentPage : 1);
		$this->limitAllPages = (@$params['limitAllPages'] ? $params['limitAllPages'] : 16);
		$this->splitter = (@$params['splitter'] ? $params['splitter'] : '&nbsp;&nbsp;');
		foreach ($params as $key => $value) $this->$key = $value;
		if (!@$this->isJs) $this->isJs = false; 
		if (!@$this->limitAllPages) $this->limitAllPages = 5; 
		
		if ($this->type == 'yandex') {
    		
		    if ($this->currentPage > 1) {
                $leftUrl = "{$this->url}page=".($this->currentPage-1);
		    } 
		    if ($this->currentPage < $this->pagesCount) {
                $rightUrl = "{$this->url}page=".($this->currentPage+1);
		    }
		    
		    if ($this->pagesCount > $this->limitAllPages + 1) $output = $this->yandexPager();	
    		else $output = $this->easyPager();
    		return array('output' => $output, 'leftUrl' => @$leftUrl, 'rightUrl' => @$rightUrl);
    		
    			
    	} elseif ($this->type == 'blog') {
    	    $output = $this->blogPager();
    	} elseif ($this->type == 'outTwo') {
    	    $output = $this->outTwoPager();    
    	}
		
		return $output;
	}
	
	public function outTwoPager()
	{
		if ($this->currentPage > 1) {
		    $output[] = $this->outTwoPattern($this->currentPage - 1);
		} else $output[] = '';
		
	    if ($this->currentPage < $this->pagesCount) {
		    $output[] = $this->outTwoPattern($this->currentPage + 1);
		} else $output[] = '';
		
		return $output;
	}
	
	public function blogPager()
	{
		$output = array();
		if ($this->currentPage < $this->pagesCount) {
		    $output[] = $this->linkPattern($this->currentPage + 1, $this->nextName);
		} 
		if ($this->currentPage > 1) {
		    $output[] = $this->linkPattern($this->currentPage - 1, $this->prevName);
		}
		
		return implode($this->splitter, $output);
	}
	
	public function easyPager()
	{
	    $output = array();
	    
		foreach (range(1, $this->pagesCount) as $i) {
			$output[] = $this->pageElement($i);			
		}
		return implode($this->splitter, $output);
	}
	
	private function pageElement($ipage)
	{
		return ($ipage == $this->currentPage ? $ipage : $this->linkPattern($ipage));		
	}
	
	private function linkPattern($ipage, $name = '')
	{
		$url = "{$this->url}page=$ipage";
		
	    if ($this->isJs) {
		    return '<span style="cursor: pointer;'.(isset($this->jsLinkStyle) ? $this->jsLinkStyle : '').'" class="js_link black" onclick="'.$this->jsFunc.'(\''.$url.'\')">' . ($name ? $name : $ipage)  . '</span>';
		} else {
		    return '<a href="' . $url  . '">' . ($name ? $name : $ipage)  . '</a>';
		}
	}
	private function outTwoPattern($ipage, $name = '')
	{
		$url = "{$this->url}page=$ipage";
		return $url;
	}
	
	private function dotElement($ipage)
	{
		return $this->linkPattern($ipage);
	}
	
	public function yandexPager()
	{
		$limitSidePages = round($this->limitAllPages / 2);
		$output = array();
		
		if ($this->currentPage - $this->limitAllPages < 1) {
			foreach (range(1, $this->limitAllPages) as $i) {
				$output[] = $this->pageElement($i);	
			}
			$output[] = $this->dotElement($this->limitAllPages + 1);
			
		} elseif ($this->currentPage + $this->limitAllPages >= $this->pagesCount) {
			$output[] = $this->dotElement($this->pagesCount - $this->limitAllPages - 1);
			foreach (range($this->pagesCount - $this->limitAllPages, $this->pagesCount) as $i) {
				$output[] = $this->pageElement($i);	
			}
			
		} else {
			$output[] = $this->dotElement($this->currentPage - $limitSidePages - 1);
			foreach (range($this->currentPage - $limitSidePages, $this->currentPage + $limitSidePages) as $i) {
				$output[] = $this->pageElement($i);	
			}
			$output[] = $this->dotElement($this->currentPage + $limitSidePages + 1);
		}

		return implode($this->splitter, $output);
	}
	
}