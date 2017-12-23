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

if((OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) || (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type'])) {

    ####### PAGE HEADER #######
    $page['title'] = 'Administration';

    require_once dirname(__FILE__).'/include/page_header.php';

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $form = "";
    if ($USE_VLANS) {

        if ($OS == 'FreeBSD') {
            $cmd = "ifconfig -g vlan";
            $result = shell_exec($cmd);
            $str = explode("\n", $result);
        }
        elseif ($OS == 'Linux') {
            $cmd = "ls /proc/net/vlan";
            $result = shell_exec($cmd);
            $str = str_replace("config", "", explode("\n", $result));
        }

        if ($str) {

            $vlan = array('' => '');
            foreach ($str as $value) {

                $vlan[$value] = $value;
            }

        $form .=
"    <form action=\"administration_apply.php\" method=\"post\">
      <table>
        <tbody>
          <tr class=\"header_top\">
            <th>
              "._('vlan')."
".combobox('', 'vlan', null, $vlan)."
              <input class=\"button\" type=\"submit\" name=\"clear\" value=\""._('clear')."\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
            </th>
          </tr>
        </tbody>
      </table>
    </form>\n";
        }
    }

    if (file_exists($LOG_FILE)) {
        $log = file_get_contents("$LOG_FILE");
    }
    else {
        $log = "";
    }

    $form .=
"    <form method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label>$LOG_FILE</label>
            </th>
          </tr>
          <tr>
            <td>
              <textarea style=\"margin-top:10px; margin-bottom:10px; margin-left:3px; width: 99%; height: 300px;\">$log</textarea>
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
