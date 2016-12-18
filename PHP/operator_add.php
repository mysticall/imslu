<?php
/*
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

//enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$sysadmin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_permissions = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

if($admin_permissions || $sysadmin_permissions) {

    if(!$sysadmin_permissions) {

        $OPERATOR_GROUPS = array(
                1 => _('Cashiers'),
                2 => _('Network technicians')
                );
    }


    ####### PAGE HEADER #######
    $page['title'] = 'Edit operator';

    require_once dirname(__FILE__).'/include/page_header.php';

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

        $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"alias\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('alias'))."\");
        document.getElementById(\"alias\").focus();
        return false;
    }
    if (document.getElementById(\"password1\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('password'))."\");
        document.getElementById(\"password1\").focus();
        return false;
    }
}
//-->
</script>
    <form action=\"operator_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('new operator')."</label>
              <label class=\"info_right\"><a href=\"operators.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('alias')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"alias\" type=\"text\" name=\"alias\" onkeyup=\"value_exists('alias', 'operators', '', '"._('That alias is already being used!')."')\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"name\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('password')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"password1\" type=\"password\" name=\"password1\" onkeyup=\"checkPass('"._('Passwords Match!')."', '"._('Passwords Do Not Match!')."');\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('password (once again)')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"password2\" type=\"password\" name=\"password2\" onkeyup=\"checkPass('"._('Passwords Match!')."', '"._('Passwords Do Not Match!')."');\">
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
$languages_unable_set = 0;

foreach ($LOCALES as $key => $value) {
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
".combobox('', 'theme', null, $THEMES)."\n
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('url (after login)')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"url\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('group')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'type', null, $OPERATOR_GROUPS)."\n
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\" onclick=\"formhash(this.form, this.form.password1, 'p1'); formhash(this.form, this.form.password2, 'p2');\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;

    require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
    header('Location: profile.php');
}
?>
