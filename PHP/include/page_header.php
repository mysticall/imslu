
<?php
require_once dirname(__FILE__).'/classes/menu.php';

$menu = new Menu();

$html =
"<!doctype html>
<html>
<head>
    <title>{$page['title']}</title>
    <meta name=\"Author\" content=\"MSIUL Developers\">
    <meta charset=\"utf-8\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"css.css\"> \n";

if ($_SESSION['data']['theme'] != 'originalgreen') {

    $css = $_SESSION['data']['theme'];

    if ($css == 'originalblue') {
        $calendar_style = "<link rel=\"stylesheet\" type=\"text/css\" href=\"js/calendar/calendar-blue.css\">";
    }

    $html .=
"    <link rel=\"stylesheet\" type=\"text/css\" href=\"styles/themes/{$css}/main.css\">
    {$calendar_style} \n";
}
else {
    
    $html .=
"    <link rel=\"stylesheet\" type=\"text/css\" href=\"js/calendar/calendar-green.css\">\n";
}

$html .=
"    <script type=\"text/javascript\" src=\"js/func.js\"></script>
    <script type=\"text/javascript\" src=\"js/password_generator.js\"></script>
    <script type=\"text/javascript\" src=\"js/calendar/calendar.js\"></script>
    <script type=\"text/javascript\" src=\"js/calendar/calendar-en.js\"></script>
    <script type=\"text/javascript\" src=\"js/calendar/calendar-setup.js\"></script>
</head>
  <body>
    <div class=\"top_container\">
".$menu->top_menu('top_menu')."
    </div>
    <div class=\"right_container\">
".$menu->right_menu('right_menu')."
      <ul>"._('version').": <br>{$VERSION}</ul>
    </div>
    <div id=\"middle_container\" class=\"middle_container\">\n";

echo $html;
