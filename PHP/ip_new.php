<?php
/*
 * IMSLU version 0.2-alpha
 *
 * Copyright © 2016 IMSLU Developers
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

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$disabled = ($admin_permissions) ? '' : ' disabled';

####### PAGE HEADER #######
$page['title'] = 'New IP';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


####### Edit #######
if (!empty($_GET['userid']) && !empty($_GET['pool'])) {

    # !!! Prevent problems !!!
    settype($_GET['userid'], "integer");

    if($_GET['userid'] == 0) {

        header("Location: users.php");
        exit;
    }

    // Select IP Address
    $sql = 'SELECT * FROM ip WHERE userid=0 AND pool = :pool LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':pool', $_GET['pool'], PDO::PARAM_STR);
    $sth->execute();
    $ip = $sth->fetch(PDO::FETCH_ASSOC);

    if (empty($ip)) {
        echo
"      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th>
              <label class=\"info_right\"><a href=\"user.php?userid={$_GET['userid']}\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td>
              <label style=\"font-size:18px; font-weight:bold; color: #ff0000;\">".
              _('Please contact your system administrator. No available IP addresses on this pool.')
              ."<label>
            </td>
          </tr>";
        exit;
    }

    ####### FreeRadius Groups #######
    //Check available Freeradius Groups if $USE_PPPoE == True
    if ($USE_PPPoE) {

        $ip['groupname'] = '';

        $sql = 'SELECT groupname FROM radgroupcheck GROUP BY groupname';
        $sth = $db->dbh->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {

            for ($i = 0; $i < count($rows); ++$i) {

                $groupname[$rows[$i]['groupname']] = $rows[$i]['groupname'];
            }
        }
        else {
    
            echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'. 
                _('Please contact your system administrator. The FreeRadius group missing.') .'<label>';

            require_once dirname(__FILE__).'/include/page_footer.php';
            exit;
        }
    }

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"ip\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IP address'))."\");
        document.getElementById(\"ip\").focus();
        return false;
    }
    if (document.getElementById(\"protocol\").value == \"PPPoE\" && document.getElementById(\"username\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('username'))."\");
        document.getElementById(\"username\").focus();
        return false;
    }
}
//-->
</script>
    <form id=\"edit\" action=\"ip_new_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label class=\"info_right\">
                <a href=\"user.php?userid={$_GET['userid']}\">["._('back')."]</a>
              </label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('IP address')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"ip\" type=\"text\" name=\"ip\" value=\"{$ip['ip']}\" onkeyup=\"value_exists('ip', 'ip_ip', '{$ip['id']}', '"._('The IP address is already being used!')."')\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('vlan')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"vlan\" value=\"{$ip['vlan']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('mac')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"mac\" value=\"{$ip['mac']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free mac')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"radio\" name=\"free_mac\" value=\"y\"";
    $form .= ($ip['free_mac'] == 'y') ? ' checked>' : '>';
    $form .= _('Yes')."
              <input class=\"checkbox\" type=\"radio\" name=\"free_mac\" value=\"n\"";
    $form .= ($ip['free_mac'] == 'n') ? ' checked>' : '>';
    $form .= _('No')."
            </td>
          </tr>\n";

    ####### PPPoe - FreeRadius #######
    if (!empty($groupname)) {
          
    $form .=
"          <tr>
            <td class=\"dt right\">
              <label>"._('FreeRadius group')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'groupname', null, $groupname)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('username')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"username\" type=\"text\" name=\"username\" value=\"{$ip['username']}\" onkeyup=\"value_exists('username', 'ip_username', '{$ip['id']}', '"._('The username is already being used!')."')\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('password')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"pass\" type=\"text\" name=\"pass\" value=\"{$ip['pass']}\">
              <label class=\"link\" onclick=\"generatepassword(document.getElementById('pass'), 8);\" >"._('generate')."</label>
            </td>
          </tr>\n";
    }

    $form .=
"          <tr>
            <td class=\"dt right\">
              <label>"._('pool')."</label>
            </td>
            <td class=\"dd\">
              {$ip['pool']}
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('protocol')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'protocol', $ip['protocol'], $protocol)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('stopped')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"radio\" name=\"stopped\" value=\"y\"";
    $form .= ($ip['stopped'] == 'y') ? ' checked' : '';
    $form .= " $disabled> "._('Yes')."
              <input class=\"checkbox\" type=\"radio\" name=\"stopped\" value=\"n\"";
    $form .= ($ip['stopped'] == 'n') ? ' checked' : '';
    $form .= " $disabled> "._('No')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" rows=\"2\">".chars($ip['notes'])."</textarea>
            </td>
          </tr>
        <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"userid\" value=\"{$_GET['userid']}\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}
?>