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
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);
$disabled = ($admin_permissions) ? '' : ' disabled';


####### PAGE HEADER #######
$page['title'] = 'Edit User';
$page['file'] = 'user_edit.php';

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
    $sql = 'SELECT name,price FROM services';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        for ($i = 0; $i < count($rows); ++$i) {

            $services[$rows[$i]['name']] = $rows[$i]['name'] .' - '. $rows[$i]['price'];
        }
    }
    else {
    
        echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'.
            _('Please contact your system administrator. The service missing.') .'<label>';

        require_once dirname(__FILE__).'/include/page_footer.php';
        exit;
    }

    ####### Get user info, ip adresses and payment #######
    $sql = 'SELECT users.*, payments.id as payment_id, payments.expires, location.name as location_name, services.name as service, services.price as price 
            FROM users
            LEFT JOIN (SELECT id, userid, expires FROM payments WHERE userid = :payments_userid ORDER BY id DESC, expires DESC LIMIT 1) AS payments
            ON users.userid = payments.userid
            LEFT JOIN services ON users.service = services.name
            LEFT JOIN location ON users.locationid = location.id
            WHERE users.userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':payments_userid', $userid, PDO::PARAM_INT);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);

    if(!$user) {

        header("Location: users.php");
        exit;
    }

    // Select user IP Addresses
    $sql = 'SELECT * FROM ip WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $ip = $sth->fetchAll(PDO::FETCH_ASSOC);

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

    // Get info for online IP Addresses
    $cmd = "cat /tmp/imslu_online_ip_addresses.tmp";
    $result = shell_exec($cmd);
    foreach (explode("\n", $result) as $value) {
        
        $ip_status[$value] = $value;
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
    $now = date ("Y-m-d H:i:s");
    $expired = ($now > $user['expires']) ? "style=\"background-color: #FFA9A9;\"" : "";

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
      <table class=\"tableinfo\">
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
              <input id=\"name\" class=\"input\" type=\"text\" name=\"name\" value=\"".chars($user['name'])."\" size=\"25\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('the location')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'locationid', $user['locationid'], $location)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"address\" value=\"".chars($user['address'])."\" size=\"25\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('phone')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"phone_number\" value=\"".chars($user['phone_number'])."\" size=\"25\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" cols=\"45\" rows=\"2\">".chars($user['notes'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('service')."</label>
            </td>
            <td class=\"dd\">\n";

    $form .= ($admin_permissions || $cashier_permissions) ? combobox('input select', 'service', $user['service'], $services)."\n" : "<label style=\"font-weight: bold;\">".chars($services[$user['service']])."</label>\n";
    $form .= 
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pay')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"pay\" value=\"";
    $form .= ($user['pay'] != 0.00) ? $user['pay'] : '';
    $form .= "\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free internet access')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"radio\" name=\"free_access\" value=\"y\"";
    $form .= ($user['free_access'] == 'y') ? 'checked' : '';
    $form .= " $disabled> "._('Yes')."
              <input class=\"input\" type=\"radio\" name=\"free_access\" value=\"n\"";
    $form .= ($user['free_access'] == 'n') ? 'checked' : '';
    $form .= " $disabled> "._('No')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('not excluding')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"radio\" name=\"not_excluding\" value=\"y\"";
    $form .= ($user['not_excluding'] == 'y') ? 'checked' : '';
    $form .= " $disabled> "._('Yes')."
              <input class=\"input\" type=\"radio\" name=\"not_excluding\" value=\"n\"";
    $form .= ($user['not_excluding'] == 'n') ? 'checked' : '';
    $form .= " $disabled> "._('No')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('active until')."</label>
            </td>
            <td class=\"dd\" $expired>
              <input class=\"input\" type=\"text\" name=\"expires\" id=\"expires\" value=\"{$user['expires']}\" $disabled>\n";

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
              <input class=\"input\" type=\"checkbox\" name=\"del_user\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">
              <input type=\"submit\" name=\"delete\" value=\""._('delete')."\">\n";
    }
    else {

        $form .=
"        <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">\n";
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
            <td class=\"bold\">"._('free mac')."</td>
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

            $ip_status = (!empty($ip_status[$ip[$i]['ip']])) ? "&nbsp;&nbsp;<span style=\"color: #00c500; font-weight:bold;\">"._('online')."</span>" : "&nbsp;&nbsp;<span style=\"color: #ff0000; font-weight:bold;\">"._('offline')."</span>";
            $sessions = (!empty($ip[$i]['username'])) ? "<label class=\"link\" onClick=\"location.href='user_pppoe_sessions.php?userid={$userid}&username={$ip[$i]['username']}'\">["._('sessions')."]</label>" : "";

            $form .=
"          <tr>
            <td class=\"right\">
              $sessions
              <label class=\"link\" onClick=\"location.href='ping.php?resource=arping&ipaddress={$ip[$i]['ip']}&vlan={$ip[$i]['vlan']}'\">[arping]</label>
              <label class=\"link\" onClick=\"location.href='ping.php?resource=ping&ipaddress={$ip[$i]['ip']}'\">[ping]</label>
              <label class=\"dt\">{$ip[$i]['ip']} $ip_status</label>
            </td>
            <td>".chars($ip[$i]['vlan'])."</td>
            <td>".chars($ip[$i]['mac'])."</td>
            <td>";
            $form .= ($ip[$i]['free_mac'] == 'y') ? _('Yes') : _('No');
            $form .=
"            </td>
            <td>".chars($ip[$i]['username'])."</td>
            <td>".chars($ip[$i]['pass'])."</td>
            <td>{$ip[$i]['pool']}</td>
            <td>{$ip[$i]['protocol']}</td>
            <td>";
            $form .= ($ip[$i]['stopped'] == 'y') ? _('Yes') : _('No');
            $form .=
"           </td>
            <td>
              <label class=\"link\" onClick=\"location.href='ip_edit.php?id={$ip[$i]['id']}'\">["._('change')."]</label>
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
".combobox('input select', 'pool', false, $pool)."
              <input type=\"submit\" name=\"new\" id=\"save\" value=\""._('new')."\">
            </td>
          </tr>
    </form>
        <tbody
       </table>";

    echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
