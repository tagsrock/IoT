<?php		
    include_once(dirname(__FILE__) . "/../wuss_login/functions.php");
    include_once("Settings/wuss_scoring.php");

    add_filter("wuss_section_heading", "wuss_add_scoring_menu",16,2);
    add_filter("wuss_section_content", "wuss_show_scoring_section", 18, 2);
    
    function wuss_add_scoring_menu($value)
    {
        $menu_button = new WussMenuItem('Leaderboards');
        return $value . $menu_button->GenerateMenuButton();
    }
    
    function wuss_show_scoring_section($content)
    {
        return (MENUTAB == LEADERBOARDS_CONSTANT) ? show_wuss_scoring_content() : $content;
    }
