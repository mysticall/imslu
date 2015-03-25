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

$page['title'] = 'User Info';
$page['file'] = 'user_info.php';

require_once dirname(__FILE__).'/include/page_header.php';

#####################################################
    // Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


if (!empty($_GET['userid'])) {


	# !!! Prevent problems !!!
	$userid = $_GET['userid'];
	settype($userid, "integer");
	if($userid == 0) {
		
		header("Location: users.php");
		exit;
	}
	
#####################################################
	// Get user info, ip addresses and payment
#####################################################

	$sql = 'SELECT users.*, payments.expires, location.name as location_name, switches.name as switch_name, traffic.name as traffic_name, traffic.price as traffic_price FROM users 
			LEFT JOIN (SELECT id, userid, expires FROM payments WHERE userid = :payments_userid ORDER BY id DESC, expires DESC LIMIT 1) AS payments
            ON users.userid = payments.userid
			LEFT JOIN traffic ON users.trafficid = traffic.trafficid
			LEFT JOIN location ON users.locationid = location.id
			LEFT JOIN switches ON users.switchid = switches.id
			WHERE users.userid = :userid LIMIT 1';
	$sth = $db->dbh->prepare($sql);
    $sth->bindValue(':payments_userid', $userid, PDO::PARAM_INT);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();
	$user_info = $sth->fetch(PDO::FETCH_ASSOC);

	if(!$user_info) {

		header("Location: users.php");
		exit;
	}

	// Select user IP Addresses
	$sql = 'SELECT ipaddress, subnet, vlan, mac, mac_info, free_mac, notes 
			FROM static_ippool 
			WHERE userid = :userid';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();
	$ip_info = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Get info for expired IP Addresses
    $cmd = "$IP rule show | awk '{ if ($5 == \"EXPIRED\") print $3;}'";
    $result = shell_exec($cmd);
    foreach (explode("\n", $result) as $value) {
        
        $ip_rule_info[$value] = $value;
    }

    // Get info for online IP Addresses
    $cmd = "cat /tmp/imslu_online_ip_addresses.tmp";
    $result = shell_exec($cmd);
    foreach (explode("\n", $result) as $value) {
        
        $ip_status[$value] = $value;
    }

	$form =
"     <table class=\"tableinfo\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label class=\"info_right\">
                <a href=\"user_edit.php?userid={$userid}\">["._('edit')."]</a>
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
              <label>".chars($user_info['name'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('the location')."</label>
            </td>
            <td class=\"dd\">
              <label>{$user_info['location_name']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('switch')."</label>
            </td>
            <td class=\"dd\">
              <label>";
	$form .= ($user_info['switch_name'] != '0') ? $user_info['switch_name'] : "";
	$form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('address')."</label>
            </td>
            <td class=\"dd\">
              <label>".chars($user_info['address'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('phone')."</label>
            </td>
            <td class=\"dd\">
              <label>".chars($user_info['phone_number'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
			<td class=\"dd\">
              <textarea name=\"notes\" cols=\"55\" rows=\"3\" readonly>".chars($user_info['notes'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('tariff plan')."</label>
            </td>
            <td class=\"dd\">
              <label>".chars($user_info['traffic_name'])." - {$user_info['traffic_price']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pay')."</label>
            </td>
            <td class=\"dd\">
              <label>";
	$form .= ($user_info['pay'] != 0.00) ? $user_info['pay'] : '';
	$form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free internet access')."</label>
            </td>
            <td class=\"dd\">
              <label>";
    $form .= ($user_info['free_access'] != 0) ? _('yes') : _('no');
    $form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('not excluding')."</label>
            </td>
            <td class=\"dd\">
              <label>";
    $form .= ($user_info['not_excluding'] != 0) ? _('yes') : _('no');
    $form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('active until')."</label>
            </td>
            <td class=\"dd\">
              <label><b>{$user_info['expires']}</b></label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('included in')."</label>
            </td>
            <td class=\"dd\">
              <label>{$user_info['created']}</label>
            </td>
          </tr>\n";

	// Show user IP addresses
	if (!empty($ip_info[0]['ipaddress'])) {

		for ($i = 0; $i < count($ip_info); ++$i) {

            $expired = (!empty($ip_rule_info[$ip_info[$i]['ipaddress']])) ? "style=\"background-color: #FFA9A9;\"" : "";
            $ip_status = (!empty($ip_status[$ip_info[$i]['ipaddress']])) ? "&nbsp;&nbsp;<span style=\"color: #00c500; font-weight:bold;\">"._('online')."</span>" : "&nbsp;&nbsp;<span style=\"color: #ff0000; font-weight:bold;\">"._('offline')."</span>";

			$form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('IP address')."</label>
            </td>
            <td class=\"dd\" $expired>
              <label style=\"font-weight:bold;\">{$ip_info[$i]['ipaddress']}/{$ip_info[$i]['subnet']}</label>
              $ip_status
              <label class=\"link\" onClick=\"location.href='ping.php?resource=ping&ipaddress={$ip_info[$i]['ipaddress']}'\">[ ping ]</label>
              <label class=\"link\" onClick=\"location.href='ping.php?resource=arping&ipaddress={$ip_info[$i]['ipaddress']}&vlan={$ip_info[$i]['vlan']}'\">[ arping ]</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('vlan')."</label>
            </td>
            <td class=\"dd\">
              <label>".chars($ip_info[$i]['vlan'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free mac')."</label>
            </td>
            <td class=\"dd\">
              <label>";
			$form .= ($ip_info[$i]['free_mac'] == 0) ? _('no') : _('yes');
			$form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('mac')."</label>
            </td>
            <td class=\"dd\">
              <label style=\"font-weight:bold;\">{$ip_info[$i]['mac']}</label>&nbsp;&nbsp; 
              <label>".chars($ip_info[$i]['mac_info'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
			<td class=\"dd\">
              <textarea name=\"notes\" cols=\"55\" rows=\"2\" readonly>".chars($ip_info[$i]['notes'])."</textarea>
            </td>
          </tr>\n";
         }
	}

#####################################################
// PPPoe - Freeradius, $USE_PPPoE must be TRUE
#####################################################
	if ($USE_PPPoE) {
		
		if($user_info['pppoe'] == 0) {
			
			$form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('use PPPoE')."</label>
            </td>
            <td class=\"dd\">
              <label>"._('no')."</label>
            </td>
          </tr>\n";
		}
		else {

			$form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('use PPPoE')."</label>
            </td>
            <td class=\"dd\">
              <label>"._('yes')."</label>
            </td>
          </tr>\n";
		  
			//Get Freeradius info for user
			$sql = 'SELECT radcheck.username, radcheck.attribute, radcheck.op, radcheck.value, radusergroup.groupname 
					FROM radcheck
					LEFT JOIN radusergroup ON radcheck.userid = radusergroup.userid 
					WHERE radcheck.userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();
			$rows = $sth->fetchAll(PDO::FETCH_ASSOC);


			if ($rows) {

                # Get info for PPPoE status
                $sql = 'SELECT nasipaddress, nasportid, acctstarttime, acctstoptime, acctsessiontime, callingstationid, framedipaddress
                        FROM radacct
                        WHERE username = :username ORDER BY acctstarttime DESC LIMIT 9';
                $sth = $db->dbh->prepare($sql);
                $sth->bindValue(':username', $rows[0]['username'], PDO::PARAM_STR);
                $sth->execute();
                $acct_info = $sth->fetchAll(PDO::FETCH_ASSOC);

                if ($acct_info) {

                  $callingstationid = array();
                  for ($i = 0; $i < count($acct_info); ++$i) {

                    if (!in_array($acct_info[$i]['callingstationid'], $callingstationid)) {
                    
                        array_push($callingstationid, $acct_info[$i]['callingstationid']);
                        $status = (!$acct_info[$i]['acctstoptime']);
                        $pppoe_status = ($status) ? 
                                "<span style=\"color: #00c500;\">"._('online')." &nbsp;&nbsp;</span><span style=\"color: #00c500;\">".time2strclock(time() - strtotime($acct_info[$i]['acctstarttime']))."&nbsp;</span>" : 
                                "<span style=\"color: #ff0000;\">"._('offline')." &nbsp;&nbsp;</span><span style=\"color: #ff0000;\">".time2strclock($acct_info[$i]['acctsessiontime'])."&nbsp;</span>";
                        $kill = ($status) ? 
                                "<label class=\"link\" onClick=\"location.href='pppd_kill.php?userid=$userid&page=info&ipaddress={$acct_info[$i]['framedipaddress']}'\">[ kill ]</label>" : "";
                        $pppoe_info = ($status) ? 
                                "<label style=\"font-weight:bold;\">{$acct_info[$i]['framedipaddress']} : <span style=\"color: #ff0000;\">{$acct_info[$i]['callingstationid']}</span> @ {$acct_info[$i]['nasipaddress']}:{$acct_info[$i]['nasportid']}</label>" : 
                                "<label style=\"font-weight:bold;\">{$acct_info[$i]['acctstarttime']} -> {$acct_info[$i]['acctstoptime']}</label>";
                        $ping = ($status) ? 
                                "<label class=\"link\" onClick=\"location.href='ping.php?resource=ping&ipaddress={$acct_info[$i]['framedipaddress']}'\">[ ping ]</label>" : "";

    				    $form .=
"          <tr>
            <td class=\"dt right\">
              <label>$pppoe_status</label>
              $kill
            </td>
            <td class=\"dd\">
              <label>$pppoe_info</label>
              <label class=\"link\" onClick=\"location.href='user_pppoe_sessions.php?userid=$userid&username={$rows[0]['username']}'\">[ "._('sessions')." ]</label>
              $ping
            </td>
          </tr>\n";
              }
            }
          }

                $form .=
"          <tr>
            <td class=\"dt right\">
              <label>"._('freeRadius group')."</label>
            </td>
            <td class=\"dd\">
              &nbsp;&nbsp;&nbsp;<label>".chars($rows[0]['groupname'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('username')."</label>
            </td>
            <td class=\"dd\">
              &nbsp;&nbsp;&nbsp;<label>".chars($rows[0]['username'])."</label>
            </td>
          </tr>\n";

				for ($i = 0; $i < count($rows); ++$i) {
				
					$form .=
"          <tr>
            <td class=\"dt right\">
              <label>{$rows[$i]['attribute']}</label>
            </td>
            <td class=\"dd\">
              <label>{$rows[$i]['op']}</label> &nbsp;
              <label>".chars($rows[$i]['value'])."</label>
            </td>
          </tr>\n";
				}
			}
		}
	}

	$form .=
"      </table>\n";

	echo $form;

	require_once dirname(__FILE__).'/include/page_footer.php';
}

?>
