
<?php
require_once dirname(__FILE__).'/classes/class.cmenu.php';

$menu = new CMenu();

if (!isset($page['file'])) {
	$page['file'] = basename($_SERVER['PHP_SELF']);
}

// page title
$page_title = isset($page['title']) ? $page['title'] : 'MSIUL';

$html =
"<!doctype html>
<html>
  <head>
    <title>$page_title</title>
    <meta name=\"Author\" content=\"MSIUL Developers\">
    <meta charset=\"utf-8\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"css.css\">
    <link rel=\"stylesheet\" type=\"text/css\" href=\"js/calendar/calendar-green.css\">\n";

$css = 'originalgeen';

$html .=
"    <link rel=\"stylesheet\" type=\"text/css\" href=\"styles/themes/$css/main.css\">
    <script type=\"text/javascript\" src=\"js/sha512.js\"></script>
    <script type=\"text/javascript\" src=\"js/func.js\"></script>
    <script type=\"text/javascript\" src=\"js/password_generator.js\"></script>
    <script type=\"text/javascript\" src=\"js/calendar/calendar.js\"></script>
    <script type=\"text/javascript\" src=\"js/calendar/calendar-en.js\"></script>
    <script type=\"text/javascript\" src=\"js/calendar/calendar-setup.js\"></script>
  </head>
  <body class=\"$css\">
    <div class=\"top_container\">
".$menu->menu_top('menu_top')."
    </div>
    <div class=\"right_container\">
".$menu->menu_right('menu_right')."
      <ul>"._('version').": <br>$VERSION</ul>
    </div>
    <div class=\"middle_container\">\n";

echo $html;
