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

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$disabled = ($admin_permissions) ? '' : ' disabled';


####### PAGE HEADER #######
$page['title'] = 'Edit IP';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


# !!! Prevent problems !!!
if (empty($_GET['id'])) {

    header("Location: users.php");
    exit;
}
else {

    settype($_GET['id'], "integer");
    if($_GET['id'] == 0) {

        header("Location: users.php");
        exit;
    }
}

####### Edit #######
if (!empty($_GET['change'])) {

    // Select pool
    $sql = 'SELECT pool FROM ip GROUP BY pool';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {

        $form =
"      <table>
        <tbody>
          <tr class=\"header_top\">
            <th>
            "._('Subnetworks')."
            <label class=\"info_right\"><a href=\"ip_edit.php?id={$_GET['id']}\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr class=\"center\">
            <td> \n";

        foreach ($rows as $value) {

            $form .=
" <a href=\"ip_edit.php?id={$_GET['id']}&pool={$value['pool']}\">{$value['pool']}</a> ";
        }

        $form .=
"            </td>
          </tr>
        </tbody>
      </table>";

        echo $form;
    }
}
elseif (!empty($_GET['pool'])) {

    $sql = 'SELECT ip FROM ip WHERE userid=0 AND pool = :pool';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':pool', $_GET['pool'], PDO::PARAM_STR);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo
"      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th>
              <label class=\"info_right\"><a href=\"ip_edit.php?id={$_GET['id']}\">["._('back')."]</a></label>
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
    else {

        $form =
"      <table>
        <tbody>
          <tr class=\"header_top\">
            <th>
            "._('IP addresses')."
            <label class=\"info_right\"><a href=\"ip_edit.php?id={$_GET['id']}\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr class=\"center\">
            <td>";

        foreach ($rows as $value) {

            $form .= "<a href=\"ip_edit.php?id={$_GET['id']}&ip={$value['ip']}\">{$value['ip']}</a>\n";
        }

        $form .=
"</td>
          </tr> 
        </tbody>
      </table>";

        echo $form;
    }
}
else {

    // Select IP Address
    $sql = 'SELECT * FROM ip WHERE id = :id';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
    $sth->execute();
    $ip = $sth->fetch(PDO::FETCH_ASSOC);

    if($ip['userid'] == 0) {

        header("Location: users.php");
        exit;
    }

    //Check available Freeradius Groups if $USE_PPPoE == True
    if ($USE_PPPoE) {

        if (!empty($ip['username']) && $ip['protocol'] == 'PPPoE') {

            $sql = 'SELECT groupname FROM radusergroup WHERE username = :username';
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':username', $ip['username']);
            $sth->execute();
            $radusergroup = $sth->fetch(PDO::FETCH_ASSOC);
        }
        else {
            $radusergroup['groupname'] = null;
        }
        $ip['groupname'] = !empty($radusergroup['groupname']) ? $radusergroup['groupname'] : '';

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

    $validate_vlan = "";
    if ($USE_VLANS) {

        if ($OS == 'FreeBSD') {
            $cmd = "ifconfig -g vlan";
            $result = shell_exec($cmd);
            $str = str_replace("\n", " ", $result);
        }
        elseif ($OS == 'Linux') {
            $cmd = "ls /proc/net/vlan";
            $result = shell_exec($cmd);
            $str = str_replace("\n", " ", $result);
        }

        if ($str) {
            $validate_vlan =
"    var vlans = \" {$str} \";
    var vlan = document.getElementById(\"vlan\").value;

    if (vlan !== \"\" && !vlans.includes(\" \" + vlan + \" \")) {

        add_new_msg(\""._('Interface does not exist.')."\");
        document.getElementById(\"vlan\").focus();
        return false;
    }";
        }
    }

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $form =
"<script type=\"text/javascript\">
<!--
window.onload = function() {
    document.getElementById('save').disabled = false;
};

function validateForm() {

    var protocol = document.getElementById(\"protocol\").value;
{$validate_vlan}

    if (document.getElementById(\"ip\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IP address'))."\");
        document.getElementById(\"ip\").focus();
        return false;
    }

    if (protocol == \"DHCP\" && document.getElementById(\"mac\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('mac'))."\");
        document.getElementById(\"mac\").focus();
        return false;
    }
    else if (protocol == \"PPPoE\" && document.getElementById(\"username\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('username'))."\");
        document.getElementById(\"username\").focus();
        return false;
    }
}

function CheckIP() {

	var value = document.getElementById('ip').value;
    var msg = '"._('The IP address does not exist!')."';
	var xmlhttp;

	if (window.XMLHttpRequest) {
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp = new XMLHttpRequest();
	}
	else {
		// code for IE6, IE5
		xmlhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");
	}

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {

            if (xmlhttp.responseText == 1) {

                add_new_msg(msg);
                document.getElementById('save').disabled = true;
            }
            else if (xmlhttp.responseText == 0) {

                document.getElementById('save').disabled = false;
                if (document.getElementById('msg')) {
                    document.getElementById('msg').remove();
                }
                value_exists('ip', 'ip_ip', '{$ip['id']}', '"._('The IP address is already being used!')."');
            }
        }
    };

    xmlhttp.open(\"GET\", \"value_exists.php?table=ip_exists&value=\"+value+\"&valueid=\", true);
    xmlhttp.send();
}
//-->
</script>
    <form id=\"edit\" action=\"ip_edit_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label class=\"info_right\">
                <a href=\"user.php?userid={$ip['userid']}\">["._('back')."]</a>
              </label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('IP address')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"ip\" type=\"text\" name=\"ip\" value=\"";
    $form .= !empty($_GET['ip']) ? $_GET['ip'] : $ip['ip'];
    $form .= "\" onkeyup=\"CheckIP()\">
              <a href=\"ip_edit.php?id={$_GET['id']}&change=1\">["._('change')."]</a>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('vlan')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"vlan\" type=\"text\" name=\"vlan\" value=\"{$ip['vlan']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('mac')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"mac\" type=\"text\" name=\"mac\" value=\"{$ip['mac']}\" onkeyup=\"value_exists('mac', 'ip_mac', '{$ip['id']}', '"._('MAC address is already being used!')."')\">
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
".combobox('', 'groupname', $radusergroup['groupname'], $groupname)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('username')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"username\" type=\"text\" name=\"username\" value=\"{$ip['username']}\" size=\"15\" onkeyup=\"value_exists('username', 'ip_username', '{$ip['id']}', '"._('The username is already being used!')."')\">
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
          </tr>\n";

    // Onli System Admin or Admin can delete user
    if($admin_permissions) {

        $form .=
"          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete IP')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"del_ip\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
              <noscript>
                <label style=\"color: red;\">"._('Please enable JavaScript')."</label>
              </noscript>
            </td>
            <td class=\"dd\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"edit\" value=\""._('save')."\" disabled>
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">\n";
    }
    else {

        $form .=
"        <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"edit\" value=\""._('save')."\" disabled>\n";
    }

        $form .=
"              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"old\" value='".json_encode($ip)."'>
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}
?>
