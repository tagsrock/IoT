<?php		
    include_once(dirname(__FILE__) . "/../wuss_login/functions.php");
    include_once("Settings/wuss_data.php");

    add_filter("wuss_section_heading", "wuss_add_data_menu",13,2);
    add_filter("wuss_section_content", "wuss_show_data_section", 15, 2);
    
    function wuss_add_data_menu($value)
    {
        $menu_button = new WussMenuItem('WUData');
        return $value . $menu_button->GenerateMenuButton();
    }
    
    function wuss_show_data_section($content, $test)
    {
        return (MENUTAB == WUDATA_CONSTANT) ? show_wuss_data_content() : $content;
    }
