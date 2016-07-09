<?php
/*
 * IMSLU version 0.1-alpha
 *
 * Copyright © 2013 IMSLU Developers
 * 
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
 
// enable debug mode
 error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

####### PAGE HEADER #######
$page['title'] = 'Operator profile';
$page['file'] = 'profile.php';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

// Security key for comparison
$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

$form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"alias\") && document.getElementById(\"alias\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('alias'))."\");
        document.getElementById(\"alias\").focus();
        return false;
    }
}
//-->
</script>
    <form action=\"profile_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('profile')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('alias')."</label>
            </td>
            <td class=\"dd\">\n";
$form .= (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']) ?
"              <input id=\"alias\" name=\"alias\" class=\"input\" type=\"text\" value=\"{$_SESSION['data']['alias']}\" onkeyup=\"value_exists('alias', 'operators', '{$_SESSION['data']['operid']}', '"._('That alias is already being used.')."')\">
              <label id=\"hint\"></label>\n" :
"              <input class=\"input\" type=\"text\" name=\"alias\" value=\"{$_SESSION['data']['alias']}\">\n";
$form .= 
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\" value=\"{$_SESSION['data']['name']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('password')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"password1\" name=\"password1\" class=\"input\" type=\"password\" onkeyup=\"checkPass('"._('Passwords Match!')."', '"._('Passwords Do Not Match!')."');\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('password (once again)')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"password2\" name=\"password2\" class=\"input\" type=\"password\" onkeyup=\"checkPass('"._('Passwords Match!')."', '"._('Passwords Do Not Match!')."');\">
              <span id=\"pass_msg\"></span>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('language')."</label>
            </td>
            <td class=\"dd\">
              <select class=\"input select\" name=\"lang\">\n";

// append languages to form list
$array = $LOCALES;
$languages_unable_set = 0;


// Search for operator languarge
foreach ($array as $key => $value) {
			
	if ($key == $_SESSION['data']['lang']) {

		$found[$key] = $value;

		unset($array[$key]);
		$locales = $found + $array;
        break;
	}	
}

foreach ($locales as $key => $value) {
	// checking if this locale exists in the system. The only way of doing it is to try and set one
	// trying to set only the LC_MONETARY locale to avoid changing LC_NUMERIC
	$locale_exists = (setlocale(LC_MONETARY , $key.'.UTF-8') || $key == 'en_US') ? 'yes' : 'no';

	if ($locale_exists != 'yes') {

		$form .=
"              <option value=\"{$key}\" disabled>{$value}</option>\n";
		$languages_unable_set++;
	}
	else {
		$form .=
"              <option value=\"{$key}\">{$value}</option>\n";
	}
}

$form .= 
"              </select>";

$form .= ($languages_unable_set > 0) ? "&nbsp; <span class=\"red\">". _('Some of locales for the languages are not installed on the web server.') ."</span> \n" : "\n";

// restoring original locale
setlocale(LC_MONETARY, "{$_SESSION['data']['lang']}.UTF-8");

$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('theme')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'theme', $_SESSION['data']['theme'], $THEMES)."\n
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('url (after login)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"url\" maxlength=\"255\" value=\"{$_SESSION['data']['url']}\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

echo $form;

require_once dirname(__FILE__).'/include/page_footer.php';
?>
