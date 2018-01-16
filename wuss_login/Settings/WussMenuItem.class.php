<?php
class WussMenuItem {
	var $package;
	var $constant;
		
	public function __construct($package)
	{

		$this->package	= $package;
		$this->constant = strtoupper($package) . '_CONSTANT';
        defined($this->constant) or define($this->constant, $package);
    }
	
	public function GenerateMenuButton()
	{
	    $active_tab = Posted("menu_tab");
	    if ($active_tab == $this->package)
	        $results = '<input type=button class="button-secondary" value="'. $this->package . '">';
            else
	        $results = '<input type=submit class="button-primary" name="menu_tab" value="'. $this->package .'">';
	    return $results;
	}

}
