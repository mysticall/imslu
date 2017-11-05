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
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);
$disabled = ($admin_permissions) ? '' : ' disabled';


####### PAGE HEADER #######
$page['title'] = 'Edit User';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


####### Edit User #######
if (!empty($_GET['userid'])) {

    # !!! Prevent problems !!!
    $userid = $_GET['userid'];
    settype($userid, "integer");

    if($userid == 0) {

        header("Location: users.php");
        exit;
    }

    ####### Services #######
    $sql = 'SELECT serviceid, name, price FROM services';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        for ($i = 0; $i < count($rows); ++$i) {

            $services[$rows[$i]['serviceid']] = $rows[$i]['name'] .' - '. $rows[$i]['price'];
        }
    }
    else {
    
        echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'.
            _('Please contact your system administrator. The service missing.') .'<label>';

        require_once dirname(__FILE__).'/include/page_footer.php';
        exit;
    }

    ####### Get user info and payment #######
    $sql = 'SELECT * FROM users WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);

    if(empty($user)) {

        header("Location: users.php");
        exit;
    }

    // Select user IP Addresses
    $sql = 'SELECT * FROM ip WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $ip = $sth->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($ip)) {

        // Select pool
        $sql = 'SELECT pool FROM ip GROUP BY pool';
        $sth = $db->dbh->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            foreach ($rows as $value) {

                $pool[$value['pool']] = $value['pool'];
            }
        }
        unset($rows);

        // Static IP activity
        if ($OS == 'FreeBSD') {

        }
        elseif ($OS == 'Linux') {
            $cmd = "ip -s neighbour show | grep -v 'FAILED'";
            $result = shell_exec($cmd);
            foreach (explode("\n", $result) as $value) {
                if (!empty($value)) {
                    $tmp = explode(" ", $value);
                    $used = ($tmp[5] == "ref") ? explode("/", $tmp[8]) : explode("/", $tmp[6]);

                    if ($used[1] < 31 || $used[2] < 31) {
                        $activity_[$tmp[0]] = $tmp[0];
                    }
                }
            }
        }

        // PPPoE activity
        if ($USE_PPPoE) {
            $cmd = "cat /tmp/ip_activity_pppoe";
            $result = shell_exec($cmd);
            foreach (explode("\n", $result) as $value) {
                if (!empty($value)) {
                    $activity_[$value] = $value;
                }
            }
        }
    }

    ####### Get avalible locations #######
    $sql = 'SELECT id,name FROM location';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    $location = array('' => '');
    if ($rows) {
        foreach ($rows as $value) {

            $location[$value['id']] = $value['name'];
        }
    }

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    // Compare date of payment
    $expire = strtotime("{$user['expires']}");
    $expired = ($user['free_access'] == 'n' && time() > $expire) ? "style=\"background-color: #FFA9A9;\"" : "";

    $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"name\").focus();
        return false;
    }
    return true;
}
//-->
</script>
    <form name=\"edit\" action=\"user_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label class=\"info_right\">
                <a href=\"user_payments.php?userid={$userid}\">["._('payments')."]</a>
                <a href=\"user_tickets.php?userid={$userid}\">["._('tickets')."]</a>
              </label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" type=\"text\" name=\"name\" value=\"".chars($user['name'])."\" size=\"25\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('location')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'locationid', $user['locationid'], $location)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('address')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"address\" value=\"".chars($user['address'])."\" size=\"25\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('phone')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"phone_number\" value=\"".chars($user['phone_number'])."\" size=\"25\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" rows=\"2\">".chars($user['notes'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('service')."</label>
            </td>
            <td class=\"dd\">\n";

    $form .= ($admin_permissions || $cashier_permissions) ? combobox('', 'serviceid', $user['serviceid'], $services)."\n" : "<label style=\"font-weight: bold;\">".chars($services[$user['serviceid']])."</label>\n";
    $form .= 
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pay')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"pay\" value=\"";
    $form .= ($user['pay'] != 0.00) ? $user['pay'] : '';
    $form .= "\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free internet access')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"radio\" name=\"free_access\" value=\"y\" ";
    $form .= ($user['free_access'] == 'y') ? 'checked' : '';
    $form .= " $disabled> "._('Yes')."
              <input class=\"checkbox\" type=\"radio\" name=\"free_access\" value=\"n\" ";
    $form .= ($user['free_access'] == 'n') ? 'checked' : '';
    $form .= " $disabled> "._('No')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('not excluding')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"radio\" name=\"not_excluding\" value=\"y\" ";
    $form .= ($user['not_excluding'] == 'y') ? 'checked' : '';
    $form .= " $disabled> "._('Yes')."
              <input class=\"checkbox\" type=\"radio\" name=\"not_excluding\" value=\"n\" ";
    $form .= ($user['not_excluding'] == 'n') ? 'checked' : '';
    $form .= " $disabled> "._('No')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('active until')."</label>
            </td>
            <td class=\"dd\" $expired>
              <input type=\"text\" name=\"expires\" id=\"expires\" value=\"{$user['expires']}\" $disabled>\n";

    $form .= (empty($disabled)) ? "
              <img src=\"js/calendar/img.gif\" id=\"f_trigger_b1\">
              <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"expires\",
                  ifFormat       :    \"%Y-%m-%d %H:%M:%S\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b1\",
                  singleClick    :    true,
                  step           :    1
                });
              </script> \n" : '';

    $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('included in')."</label>
            </td>
            <td class=\"dd\">
              <label>{$user['created']}</label>
            </td>
          </tr>\n";

    // Onli System Admin or Admin can delete user
    if($admin_permissions) {

        $form .=
"          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete user')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"del_user\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input class=\"button\" type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">\n";
    }
    else {

        $form .=
"        <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input class=\"button\" type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">\n";
    }

        $form .=
"              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"old\" value='".json_encode($user)."'>
              <input type=\"hidden\" name=\"ip\" value='".json_encode($ip)."'>
            </td>
          </tr>
        </tbody>
      </table>
    </form>
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"odd_row\">
            <td class=\"dt right\">"._('IP address')."</td>
            <td class=\"bold\">"._('vlan')."</td>
            <td class=\"bold\">"._('mac')."</td>
            <td class=\"bold\">"._('username')."</td>
            <td class=\"bold\">"._('password')."</td>
            <td class=\"bold\">"._('pool')."</td>
            <td class=\"bold\">"._('protocol')."</td>
            <td class=\"bold\">"._('stopped')."</td>
            <td class=\"bold\">"._('action')."</td>
          </tr>\n";

    ####### Show IP Addresses #######
    if (!empty($ip)) {
        for ($i = 0; $i < count($ip); ++$i) {

            $ip_activity = (!empty($activity_[$ip[$i]['ip']])) ? "<span style=\"color: #00c500; font-weight:bold;\">"._('online')."</span>" : "<span style=\"color: #ff0000; font-weight:bold;\">"._('offline')."</span>";
            $sessions = ($ip[$i]['protocol'] == "PPPoE") ? "<a class=\"link\" href=\"user_pppoe_sessions.php?userid={$userid}&username={$ip[$i]['username']}\">["._('sessions')."]</a>" : "";
            $kill = ($ip[$i]['protocol'] == "PPPoE" && !empty($activity_[$ip[$i]['ip']])) ? "<a class=\"link\" href=\"pppd_kill.php?userid={$userid}&ip={$ip[$i]['ip']}\">[ kill ]</a>" : "";

            $form .=
"          <tr>
            <td class=\"right\">
              $kill
              $sessions
              <a class=\"link\" href=\"ping.php?resource=arping&ipaddress={$ip[$i]['ip']}&vlan={$ip[$i]['vlan']}\">[arping]</a>
              <a class=\"link\" href=\"ping.php?resource=ping&ipaddress={$ip[$i]['ip']}\">[ping]</a>
              <label class=\"dt\">{$ip[$i]['ip']} $ip_activity</label>
            </td>
            <td>".chars($ip[$i]['vlan'])."</td>
            <td>".chars($ip[$i]['mac'])."</td>
            <td>".chars($ip[$i]['username'])."</td>
            <td>".chars($ip[$i]['pass'])."</td>
            <td>{$ip[$i]['pool']}</td>
            <td>{$ip[$i]['protocol']}</td>
            <td>";
            $form .= ($ip[$i]['stopped'] == 'y') ? _('Yes') : _('No');
            $form .=
"           </td>
            <td>
              <a class=\"link\" href=\"ip_edit.php?id={$ip[$i]['id']}\">["._('change')."]</a>
            </td>
          </tr>
          <tr>
            <td>
              <label>".chars($ip[$i]['notes'])."</label>
            </td>
          </tr>\n";
        }
    }

    $form .=
"    <form name=\"ip_new\" action=\"ip_new.php\" method=\"get\">
          <tr class=\"odd_row\" >
            <td class=\"right\" colspan=\"10\">
              <input type=\"hidden\" name=\"userid\" value=\"{$userid}\">
".combobox('', 'pool', false, $pool)."
              <input class=\"button\" type=\"submit\" name=\"new\" value=\""._('new')."\">
            </td>
          </tr>
    </form>
        </tbody>
       </table>";

    echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
