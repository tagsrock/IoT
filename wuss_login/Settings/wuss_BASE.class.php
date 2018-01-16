<?php
include_once(dirname(__FILE__) . "/../../wuss_login/functions.php");

class wuss_BASE {

    function __construct()
    {
        add_filter("wuss_section_heading", array($this, "create_home_button"), 12,2);
        add_filter("wuss_section_content", "wuss_default_content",14,2);
    }

    function create_section_heading($menu_tab)
	{
		?>
		<table id="theme-options-wrap" class="widefat"><tr><td><form method="POST" action="">
		<?php
            $value = '';
			echo apply_filters("wuss_section_heading", $value);
		?>
        </form></td></tr></table>
		<?php
	}

    function create_home_button($value)
    {
	    $menu_item = new WussMenuItem('WUSS');
	    return $menu_item->GenerateMenuButton();
    }

}
