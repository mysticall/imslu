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
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);
$disabled = ($admin_permissions) ? '' : ' disabled';


###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'Edit User';
$page['file'] = 'user_edit.php';

require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


###################################################################################################
	// Edit User
###################################################################################################

if (!empty($_GET['userid'])) {

	# !!! Prevent problems !!!
	$userid = $_GET['userid'];
	settype($userid, "integer");
	if($userid == 0) {
		
		header("Location: users.php");
		exit;
	}
	
#####################################################
	// Get avalible tariff plans
#####################################################
	$sql = 'SELECT trafficid,name,price FROM traffic';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		for ($i = 0; $i < count($rows); ++$i) {

			$tariff_plan[$rows[$i]['trafficid']] = $rows[$i]['name'] .' - '. $rows[$i]['price'];
		}
	}
	else {
	
		echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'.
			_('Please contact your system administrator. Not created tariff plan in the "Traffic control"') .'<label>';

		require_once dirname(__FILE__).'/include/page_footer.php';
		exit;
	}

#####################################################
	// Get avalible Freeradius Groups
#####################################################
	//Check available Freeradius Groups if $USE_PPPoE == True
	if ($USE_PPPoE) {
		
		$sql = 'SELECT groupname FROM radgroupcheck GROUP BY groupname';
		$sth = $db->dbh->prepare($sql);
		$sth->execute();
		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

		if ($rows) {

			for ($i = 0; $i < count($rows); ++$i) {

				$fr_groupname[$rows[$i]['groupname']] = $rows[$i]['groupname'];
			}
		}
		else {
	
			echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'. 
				_('Please contact your system administrator. Not created FreeRADIUS group in the "Groups"') .'<label>';

			require_once dirname(__FILE__).'/include/page_footer.php';
			exit;
		}
	}

#####################################################
	// Get user info, ip adresses and payment
#####################################################
	$sql = 'SELECT users.*, payments.id as payment_id, payments.expires, location.name as location_name, switches.name as switch_name, traffic.name as traffic_name, traffic.price as traffic_price 
			FROM users
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
	
	// Add in Session users info for later comparison
	$_SESSION['old_user_info'] = $user_info;

    // Select user IP Addresses
    $sql = 'SELECT * FROM static_ippool WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $ip_info = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Add in Session ip info for later comparison
    $_SESSION['old_ip_info'] = $ip_info;

    // Get info for expired IP Addresses
    $cmd = "$IP rule show | awk '{ if ($5 == \"EXPIRED\") print $3;}'";
    $result = shell_exec($cmd);
    foreach (explode("\n", $result) as $value) {
        
        $ip_rule_info[$value] = $value;
    }

    // Add in Session stopped ip addresses for later comparison
    $_SESSION['ip_rule_info'] = $ip_rule_info;

    // Get info for online IP Addresses
    $cmd = "cat /tmp/imslu_online_ip_addresses.tmp";
    $result = shell_exec($cmd);
    foreach (explode("\n", $result) as $value) {
        
        $ip_status[$value] = $value;
    }
	
#####################################################
	// Get avalible locations
#####################################################
	$sql = 'SELECT id,name FROM location';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		
		for ($i = 0; $i < count($rows); ++$i) {

			$location_name[$rows[$i]['id']] = $rows[$i]['name'];
		}
	}
		
	if (!empty($location_name)) {
		
		$location = array('' => '') + $location_name;
	}
	else {
		$location = array('' => '');
	}

#####################################################
	// Get avalible switches
#####################################################
	$sql = 'SELECT id,name FROM switches';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		
		for ($i = 0; $i < count($rows); ++$i) {

			$switch_name[$rows[$i]['id']] = $rows[$i]['name'];
		}
	}
		
	if (!empty($switch_name)) {
		
		$switches = array('' => '') + $switch_name;
	}
	else {
		$switches = array('' => '');
	}

	$use_pppoe = array('' => '', 'PPPoE' => _('yes'));

	// Security key for comparison
	$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

	# Check rights to adding new IP address
	$add_ip = ($admin_permissions || $technician_permissions) ? "<a href=\"user_edit.php?userid={$userid}&new_ip=1\">["._('add IP address')."]</a>" : '';

	$form =
"    <form name=\"edit_user\" action=\"user_edit_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label class=\"info_right\">
                {$add_ip}
                <a href=\"user_info.php?userid={$userid}\">["._('info')."]</a>
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
              <input class=\"input\" type=\"text\" name=\"user[name]\" value=\"".chars($user_info['name'])."\" size=\"35\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('the location')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'user[locationid]', $user_info['locationid'], $location)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('switch')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'user[switchid]', $user_info['switchid'], $switches)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"user[address]\" value=\"".chars($user_info['address'])."\" size=\"35\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('phone')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"user[phone_number]\" value=\"".chars($user_info['phone_number'])."\" size=\"35\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
			<td class=\"dd\">
              <textarea name=\"user[notes]\" cols=\"55\" rows=\"3\">".chars($user_info['notes'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('tariff plan')."</label>
            </td>
            <td class=\"dd\">\n";

	$form .= ($admin_permissions || $cashier_permissions) ? combobox('input select', 'user[trafficid]', $user_info['trafficid'], $tariff_plan)."\n" : "<label style=\"font-weight: bold;\">".chars($tariff_plan[$user_info['trafficid']])."</label>\n";
	$form .= 
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pay')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"user[pay]\" value=\"";
	$form .= ($user_info['pay'] != 0.00) ? $user_info['pay'] : '';
	$form .= "\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free internet access')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"user[free_access]\"";
	$form .= ($user_info['free_access'] != 0) ? 'checked' : '';
	$form .= " $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('not excluding')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"user[not_excluding]\"";
	$form .= ($user_info['not_excluding'] != 0) ? 'checked' : '';
	$form .= " $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('active until')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"user[expires]\" id=\"user_expires\" value=\"{$user_info['expires']}\" $disabled>\n";

	$form .= (empty($disabled)) ? "
              <img src=\"js/calendar/img.gif\" id=\"f_trigger_b1\">
              <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"user_expires\",
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
              <label>{$user_info['created']}</label>
            </td>
          </tr>\n";

#####################################################
	// Show Static IP Addresses for user
#####################################################
	if (!empty($ip_info)) {
		for ($i = 0; $i < count($ip_info); ++$i) {
			
			$user_ip = array($ip_info[$i]['ipaddress'] => $ip_info[$i]['ipaddress']) + array('' => '');

            $expired = (!empty($ip_rule_info[$ip_info[$i]['ipaddress']])) ? "style=\"background-color: #FFA9A9;\"" : "";
            $ip_status = (!empty($ip_status[$ip_info[$i]['ipaddress']])) ? "&nbsp;&nbsp;<span style=\"color: #00c500; font-weight:bold;\">"._('online')."</span>" : "&nbsp;&nbsp;<span style=\"color: #ff0000; font-weight:bold;\">"._('offline')."</span>";

			$form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('IP address')."</label>
            </td>
            <td class=\"dd\" $expired> \n";

			$form .= ($admin_permissions) ? combobox('input select', "static_ippool[$i][ipaddress]", NULL, $user_ip)."\n" : "<label style=\"font-weight: bold;\">{$ip_info[$i]['ipaddress']}</label>\n";

			$form .=
"              $ip_status
              <label class=\"link\" onClick=\"location.href='ping.php?resource=ping&ipaddress={$ip_info[$i]['ipaddress']}'\">[ ping ]</label>
              <label class=\"link\" onClick=\"location.href='ping.php?resource=arping&ipaddress={$ip_info[$i]['ipaddress']}&vlan={$ip_info[$i]['vlan']}'\">[ arping ]</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('vlan')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"static_ippool[$i][vlan]\" value=\"".chars($ip_info[$i]['vlan'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free mac')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"static_ippool[$i][free_mac]\" ";
			$form .= ($ip_info[$i]['free_mac'] != 0) ? 'checked' : '';
			$form .= ">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('mac')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"static_ippool[$i][mac]\" value=\"".chars($ip_info[$i]['mac'])."\"> &nbsp; &nbsp;
              <label>{$ip_info[$i]['mac_info']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"hidden\" name=\"static_ippool[$i][name]\" value=\"".chars($user_info['name'])."\">
              <textarea name=\"static_ippool[$i][notes]\" cols=\"55\" rows=\"2\">".chars($ip_info[$i]['notes'])."</textarea>
            </td>
          </tr>\n";
		}
	}

#####################################################
	// Add new IP address
#####################################################

	if (!empty($_GET['new_ip'])) {

        // Get avalible IP addresses
        $sql = 'SELECT ipaddress FROM static_ippool WHERE userid = :userid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', 0, PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            for ($i = 0; $i < count($rows); ++$i) {

               $ip[$rows[$i]['ipaddress']] = $rows[$i]['ipaddress'];
            }
            $ip_addresses = array('' => '') + $ip;
        }
        else {
    
            echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'. 
                _('Please contact your system administrator. Not added static IP addresses in the "Static IP addresses"') .'<label>';

            require_once dirname(__FILE__).'/include/page_footer.php';
            exit;
        }

		$form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('IP address')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'new_ip[ipaddress]', NULL, $ip_addresses)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('vlan')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"new_ip[vlan]\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free mac')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"new_ip[free_mac]\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('mac')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"new_ip[mac]\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"hidden\" name=\"new_ip[name]\" value=\"".chars($user_info['name'])."\" >
              <textarea name=\"new_ip[notes]\" cols=\"55\" rows=\"2\"></textarea>
            </td>
          </tr>\n";
	}

	$form .=
"        </thead>
        <tbody id=\"tbody\">\n";

#####################################################
// PPPoe - Freeradius, $USE_PPPoE must be TRUE
#####################################################

	if (!empty($fr_groupname)) {

		// Radcheck
		$radcheck_attributes = array('' => '', 'Calling-Station-Id' => 'Calling-Station-Id');
											
#####################################################
	// pppoe == 0 - PPPoE account not created	
#####################################################	
		if($user_info['pppoe'] == 0) {
			
			$form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('use PPPoE')."</label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'use_pppoe', $use_pppoe, "add_pppoe('tbody', this[this.selectedIndex].value)")."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('freeRadius group')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'groupname', null, $fr_groupname)."
            </td>
          </tr> \n";
		}
		
#####################################################
	// pppoe == 1 - PPPoE account created
#####################################################
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
			$sql = 'SELECT radcheck.id, radcheck.username, radcheck.attribute, radcheck.op, radcheck.value, radusergroup.groupname FROM radcheck
					LEFT JOIN radusergroup ON radcheck.userid = radusergroup.userid 
					WHERE radcheck.userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();
			$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

			if ($rows) {

                // Add in Session for later comparison
                $_SESSION['radcheck'] = $rows;

                $fr_group = ($admin_permissions || $cashier_permissions) ? combobox('input select', 'freeradius[0][groupname]', $rows[0]['groupname'], $fr_groupname) : "<label style=\"font-weight: bold;\">{$rows[0]['groupname']}</label>";
			
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
                                "<label class=\"link\" onClick=\"location.href='pppd_kill.php?userid=$userid&page=edit&ipaddress={$acct_info[$i]['framedipaddress']}'\">[ kill ]</label>" : "";
                        $pppoe_info = ($status) ? 
                                "<label style=\"font-weight:bold;\">{$acct_info[$i]['framedipaddress']} : <span style=\"color: #ff0000;\">{$acct_info[$i]['callingstationid']}</span> @ {$acct_info[$i]['nasipaddress']}:{$acct_info[$i]['nasportid']}</label>" : 
                                "<label style=\"font-weight:bold;\">{$acct_info[$i]['acctstarttime']} -> {$acct_info[$i]['acctstoptime']}</label>";
                        $ping = ($status) ? 
                                "<label class=\"link\" onClick=\"location.href='ping.php?resource=ping&ipaddress={$acct_info[$i]['framedipaddress']}'\">[ ping ]</label>" : "";
                        $framedipaddress = ($status) ? 
                                "<input class=\"input\" type=\"hidden\" name=\"freeradius[framedipaddress][{$acct_info[$i]['framedipaddress']}]\" value=\"{$acct_info[$i]['framedipaddress']}\">" : "";

                        $form .=
"          <tr>
            <td class=\"dt right\">
              <label>$pppoe_status</label>
              $kill
            </td>
            <td class=\"dd\">
              $framedipaddress
              <label>$pppoe_info</label>
              <label class=\"link\" onClick=\"location.href='user_pppoe_sessions.php?userid=$userid&username={$rows[0]['username']}'\">[ "._('sessions')." ]</label>
              $ping
            </td>
          </tr>\n";
              }
            }
          }

                $form .=
"          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('add attribute')."</label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'new_attribute', $radcheck_attributes, "add_attribute('tbody', 'attribute', this[this.selectedIndex].value)")."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('freeRadius group')."</label>
            </td>
            <td class=\"dd\">
            $fr_group
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('username')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"username\" class=\"input\" type=\"text\" name=\"freeradius[0][username]\" value=\"".chars($rows[0]['username'])."\" onkeyup=\"user_exists('username', 'radcheck')\"><label id=\"hint\"></label> 
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>Cleartext-Password</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[0][id]\" value=\"{$rows[0]['id']}\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[0][attribute]\" value=\"{$rows[0]['attribute']}\">
              <input id=\"password\" class=\"input\" type=\"text\" name=\"freeradius[0][value]\" value=\"".chars($rows[0]['value'])."\">
              <label class=\"generator\" onclick=\"generatepassword(document.getElementById('password'), 8);\" >"._('generate')."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>Simultaneous-Use</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[1][id]\" value=\"{$rows[1]['id']}\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[1][attribute]\" value=\"{$rows[1]['attribute']}\">
              <input id=\"password\" class=\"input\" type=\"text\" name=\"freeradius[1][value]\" value=\"{$rows[1]['value']}\">
            </td>
          </tr>\n";

				$form .=
"          <tr>
            <td class=\"dt right\">
              <label>Expiration</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[2][id]\" value=\"{$rows[2]['id']}\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[2][attribute]\" value=\"{$rows[2]['attribute']}\">
              <input class=\"input\" type=\"text\" name=\"freeradius[2][value]\" id=\"expiration\" value=\"{$rows[2]['value']}\" $disabled>\n";

				$form .= empty($disabled) ? "
              <img src=\"js/calendar/img.gif\" id=\"f_trigger_b2\">
              <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"expiration\",
                  ifFormat       :    \"%d %b %Y %H:%M:%S\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b2\",
                  singleClick    :    true,
                  step           :    1
                });
              </script>\n" : '';

				$form .=              
"            </td>
          </tr>\n";

				for ($i = 3; $i < count($rows); ++$i) {
				
					$form .=
"          <tr>
            <td class=\"dt right\">
              <label>{$rows[$i]['attribute']}</label>
".combobox('input select', 'freeradius['.$i.'][op]', $rows[$i]['op'], $freeradius_op)."
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[$i][id]\" value=\"{$rows[$i]['id']}\">
              <input class=\"input\" type=\"hidden\" name=\"freeradius[$i][attribute]\" value=\"{$rows[$i]['attribute']}\">
              <input class=\"input\" type=\"text\" name=\"freeradius[$i][value]\" value=\"".chars($rows[$i]['value'])."\">
            </td>
          </tr>\n";
				}
			}
		}

		$form .=
"        </tbody> \n";
	}

	// Onli System Admin or Admin can dalete user
	if($admin_permissions) {
	
		$form .=
"        <tfoot> \n";

		if ($USE_PPPoE && $user_info['pppoe'] == 1) {
			
			$form .=
"          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete PPPoE')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"del_pppoe\">
            </td>
          </tr>\n";
		}

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
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"change_user\" id=\"save\" value=\""._('save')."\">
              <input type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tfoot>\n";
	}
	else {
			
		$form .=
"        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"change_user\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot> \n";
	}

	$form .=
"      </table>
    </form> \n";

	echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
