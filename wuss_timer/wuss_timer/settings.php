<?php		
    include_once(dirname(__FILE__) . "/../wuss_login/functions.php");
    include_once("Settings/wuss_timers.php");

    add_filter("wuss_section_heading", "wuss_add_timers_menu",14,2);
    add_filter("wuss_section_content", "wuss_show_timers_section", 16, 2);
    
    function wuss_add_timers_menu($value)
    {
        $menu_button = new WussMenuItem('Timers');
        return $value . $menu_button->GenerateMenuButton();
    }
    
    function wuss_show_timers_section($content)
    {
        return (MENUTAB == TIMERS_CONSTANT) ? show_wuss_timers_content() : $content;
    }
