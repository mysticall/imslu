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

require_once dirname(__FILE__).'/include/common.inc.php';

if (!CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {
	header('Location: index.php');
	exit;
}
if ($_SESSION['form_key'] !== $_POST['form_key']) {
	header('Location: index.php');
	exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/network.inc.php';

$db = new CPDOinstance();
$admin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type'] || OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);
$cashier_rights = (OPERATOR_TYPE_CASHIER == CWebOperator::$data['type']);
$technician_rights = (OPERATOR_TYPE_TECHNICIAN == CWebOperator::$data['type']);


###################################################################################################
// Delete USER or PPPoe account
###################################################################################################

// Onli System Admin or Admin can dalete User or PPPoe account
if (!empty($_POST['delete']) && $admin_rights) {

	$old_ip = $_SESSION['old_ip_info'];
	$new_ip = $_POST['static_ippool'];
	$userid = $_SESSION['old_user_info']['userid'];

	if (!empty($_POST['delete']) && !empty($_POST['del_user'])) {

		$sql = 'DELETE FROM `users` WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, "User: {$_SESSION['old_user_info']['name']} is deleted.", "User info\n".json_encode($_SESSION['old_user_info'])." \nIP info\n".json_encode($_SESSION['old_ip_info'])." \nFreeRadius\n".json_encode($_SESSION['radcheck']));

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

		for ($i = 0; $i < count($old_ip); ++$i) {

			$old_ip_values = $old_ip[$i];
			$new_ip_values = $new_ip[$i];
			$new_ip_values['userid'] = 0;

			if ($USE_VLANS) {

				$ip_info = comparison_ip_vlan_changes($db, $old_ip_values, $new_ip_values);
			}
			else {
				$ip_info = comparison_ip_changes($db, $old_ip_values, $new_ip_values);
			}


			$sql = 'UPDATE `static_ippool` SET userid = :userid, trafficid = :trafficid, vlan = :vlan, mac = :mac, 
					mac_info = :mac_info, free_mac = :free_mac, name = :name, notes = :notes  WHERE id = :id AND ipaddress = :ipaddress';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':userid', 0);
			$sth->bindValue(':trafficid', 0);
			$sth->bindValue(':vlan', '');
			$sth->bindValue(':mac', '');
			$sth->bindValue(':mac_info', '');
			$sth->bindValue(':free_mac', 0);
			$sth->bindValue(':name', '');
			$sth->bindValue(':notes', '');
			$sth->bindValue(':id', $old_ip_values['id'], PDO::PARAM_INT);
			$sth->bindValue(':ipaddress', $old_ip_values['ipaddress'], PDO::PARAM_INT);
			$sth->execute();

			$_SESSION['msg'] .= !empty($ip_info['msg']) ? "{$ip_info['msg']} <br>" : '';
		}

        # if PPPoE session, kill
        if (count($_POST['freeradius']['framedipaddress']) > 0) {

            foreach ($_POST['freeradius']['framedipaddress'] as $value) {
                
                if ($value) {

                    $cmd = "$SUDO $PYTHON $IMSLU_SCRIPTS/pppd_kill.py $value 2>&1";
                    $result = shell_exec($cmd);

                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
            }
        }

		unset($_POST);
		header("Location: profile.php");
		exit;
	}

	if (isset($_POST['delete']) && isset($_POST['del_pppoe'])) {

		if((empty($_POST['static_ippool'][0]['ipaddress']) && empty($_POST['new_ip']['ipaddress'])) && (empty($_POST['freeradius'][0]['username']) && empty($_POST['pppoe']['username']))) {

			$_SESSION['msg'] .= _('You can not delete IP address and PPPoE account simultaneously.').'<br>';

			unset($_POST);
			header("Location: user_edit.php?userid={$_SESSION['old_user_info']['userid']}");
			exit;
		}
	
		$sql = 'DELETE FROM `radcheck` WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();

		$sql = 'UPDATE `users` SET pppoe = :pppoe WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':pppoe', 0);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, "PPPoE account is deleted - ID: {$_SESSION['old_user_info']['userid']}, User: {$_SESSION['old_user_info']['name']}.", "FreeRadius\n".json_encode($_SESSION['radcheck']));

        # if PPPoE session, kill
        if (count($_POST['freeradius']['framedipaddress']) > 0) {

            foreach ($_POST['freeradius']['framedipaddress'] as $value) {
                
                if ($value) {

                    $cmd = "$SUDO $PYTHON $IMSLU_SCRIPTS/pppd_kill.py $value 2>&1";
                    $result = shell_exec($cmd);

                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
            }
        }

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
		unset($_POST);
		header("Location: user_edit.php?userid={$_SESSION['old_user_info']['userid']}");
		exit;
	}	
}


###################################################################################################
	// Save changes
###################################################################################################

if (!empty($_POST['change_user'])) {

	if(empty($_POST['user']['name'])) {

		$_SESSION['msg'] .= _('Name cannot empty.').'<br>';
		header("Location: user_edit.php?userid={$_SESSION['old_user_info']['userid']}");
	exit;
	}

	if(!empty($_POST['freeradius']) && (empty($_POST['freeradius'][0]['username']) || empty($_POST['freeradius'][0]['value']))) {

		$_SESSION['msg'] .= _('PPPoE username and password cannot empty.').'<br>';
		header("Location: user_edit.php?userid={$_SESSION['old_user_info']['userid']}");
	exit;
	}
	
	if(!empty($_POST['use_pppoe']) && (empty($_POST['pppoe']['username']) || empty($_POST['pppoe']['password']))) {

		$_SESSION['msg'] .= _('Enter PPPoE username and password.').'<br>';
		header("Location: user_edit.php?userid={$_SESSION['old_user_info']['userid']}");
	exit;
	}
	
	if((empty($_POST['static_ippool'][0]['ipaddress']) && empty($_POST['static_ippool'][1]['ipaddress']) && empty($_POST['new_ip']['ipaddress'])) && (empty($_POST['freeradius'][0]['username']) && empty($_POST['pppoe']['username']))) {

		$_SESSION['msg'] .= _('You can not delete IP address and PPPoE account simultaneously.').'<br>';
		header("Location: user_edit.php?userid={$_SESSION['old_user_info']['userid']}");
	exit;
	}	
#####################################################		
	// Comparison user information
#####################################################

	$data_now = date('Y-m-d H:i:s');
	$ip_addresses = !empty($_POST['static_ippool']) ? $_POST['static_ippool'] : '';
    $ip_rule_info = $_SESSION['ip_rule_info'];

	# Here there are too many checks, because table "users" is indexed.
	# It is not advisable, to make unnecessary entries in this table.
	$old_user_info = $_SESSION['old_user_info'];
	$new_user_info = $_POST['user'];
	$userid = $old_user_info['userid'];
	$update = array();

	if ($old_user_info['name'] != $new_user_info['name']) {
		
		$update['users']['name'] = strip_tags($new_user_info['name']);

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "First Name Surname is changed - ID: $userid.", "{$old_user_info['name']}", "{$new_user_info['name']}");
	}
	$new_user_info['locationid'] = !empty($new_user_info['locationid']) ? $new_user_info['locationid'] : 0;
	if ($old_user_info['locationid'] != $new_user_info['locationid']) {
		
		$update['users']['locationid'] = $new_user_info['locationid'];
	}
	if ($old_user_info['address'] != $new_user_info['address']) {
		
		$update['users']['address'] = strip_tags($new_user_info['address']);
	}
	if ($old_user_info['phone_number'] != $new_user_info['phone_number']) {
		
		$update['users']['phone_number'] = strip_tags($new_user_info['phone_number']);
	}
	if ($old_user_info['notes'] != $new_user_info['notes']) {
		
		$update['users']['notes'] = $new_user_info['notes'];
	}
	if ((!empty($new_user_info['trafficid']) && $new_user_info['trafficid'] != 0) && ($old_user_info['trafficid'] != $new_user_info['trafficid']) && ($admin_rights || $cashier_rights)) {
		
		$update['users']['trafficid'] = $new_user_info['trafficid'];

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "The tariff plan is changed - ID: $userid, User: {$new_user_info['name']}.", "Traffic ID - {$old_user_info['trafficid']}", "Traffic ID - {$new_user_info['trafficid']}");
	}
	
	$new_user_info['pay'] = !empty($new_user_info['pay']) ? $new_user_info['pay'] : '0.00';
	if (($old_user_info['pay'] != $new_user_info['pay']) && ($admin_rights)) {

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_PAYMENTS, "Pay is changed - ID: $userid, User: {$new_user_info['name']}.", "Pay - {$old_user_info['pay']}", "Pay - {$new_user_info['pay']}");		

		$update['users']['pay'] = $new_user_info['pay'];
	}

	// Checks for free internet access
	$new_user_info['free_access'] = !empty($new_user_info['free_access']) ? 1 : 0;
	if (($old_user_info['free_access'] != $new_user_info['free_access']) && $admin_rights) {

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "Free Internet Access is changed - ID: $userid, User: {$new_user_info['name']}.", "{$old_user_info['free_access']}", "{$new_user_info['free_access']}");		
		
		$update['users']['free_access'] = $new_user_info['free_access'];

		// Start internet access
		if ($new_user_info['free_access'] == 1 && !empty($_SESSION['old_ip_info'][0]['ipaddress'])) {

			for ($i = 0; $i < count($ip_addresses); ++$i) {
		
				$result = shell_exec("$SUDO $IP rule del from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
			}
		}
		// Stop internet access
		if ($new_user_info['free_access'] == 0 && !empty($_SESSION['old_ip_info'][0]['ipaddress']) && (empty($new_user_info['expires']) || $new_user_info['expires'] < $data_now)) {

			for ($i = 0; $i < count($ip_addresses); ++$i) {
				
                if (empty($ip_rule_info[$ip_addresses[$i]['ipaddress']])) {
				    $result = shell_exec("$SUDO $IP rule add from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				    $_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is stopped.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Stopping internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
                }
			}
		}		
	}
		
	$new_user_info['not_excluding'] = !empty($new_user_info['not_excluding']) ? 1 : 0;
	if (($old_user_info['not_excluding'] != $new_user_info['not_excluding']) && $admin_rights) {

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "Not excluding is changed - ID: $userid, User: {$new_user_info['name']}.", "{$old_user_info['not_excluding']}", "{$new_user_info['not_excluding']}");		
				
		$update['users']['not_excluding'] = $new_user_info['not_excluding'];
	}
	
	if ($old_user_info['switchid'] != $new_user_info['switchid']) {
		
		$update['users']['switchid'] = $new_user_info['switchid'];
	}

	if (!empty($update['users'])) {

		$i = 1;
		foreach($update['users'] as $key => $value) {
			$keys[$i] = $key;
   			$values[$i] = $value;

		$i++;
		}

		$sql = 'UPDATE users SET '.implode(' = ?, ', $keys).' = ? WHERE userid = ?';

		array_push($values, $userid);
		$db->prepare_array($sql, $values);
	}
		
	# Check for changes on payments and update
	if (!empty($new_user_info['expires']) && ($old_user_info['expires'] != $new_user_info['expires']) && $admin_rights) {

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "Active until is changed - ID: $userid, User: {$new_user_info['name']}.", "{$old_user_info['expires']}", "{$new_user_info['expires']}");

		$expires = $new_user_info['expires'];

		$sql = 'UPDATE `payments` SET expires = :expires WHERE userid = :userid AND id = :id';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':expires', $expires, PDO::PARAM_INT);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
        $sth->bindValue(':id', $old_user_info['payment_id'], PDO::PARAM_INT);
		$sth->execute();

		// Start internet access
		if ($new_user_info['free_access'] == 0 && $new_user_info['expires'] > $data_now && !empty($_SESSION['old_ip_info'][0]['ipaddress'])) {

			for ($i = 0; $i < count($ip_addresses); ++$i) {
						
				$result = shell_exec("$SUDO $IP rule del from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
			}
		}
		// Stop internet access
		if ($new_user_info['free_access'] == 0 && $new_user_info['expires'] < $data_now && !empty($_SESSION['old_ip_info'][0]['ipaddress'])) {

            for ($i = 0; $i < count($ip_addresses); ++$i) {
				    
                if (empty($ip_rule_info[$ip_addresses[$i]['ipaddress']])) {
					$result = shell_exec("$SUDO $IP rule add from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
					$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is stopped.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Stopping internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
				}
			}
		}
	}

#####################################################
	// Comparison static IP Addresses
#####################################################

	if (!empty($_SESSION['old_ip_info'][0]['ipaddress'])) {
		
		$old_ip = $_SESSION['old_ip_info'];
		$new_ip = $_POST['static_ippool'];

		for ($i = 0; $i < count($old_ip); ++$i) {
				
			$old_ip_values = $old_ip[$i];
			$new_ip_values = $new_ip[$i];

			$new_ip_values['name'] = $new_user_info['name'];
			
			// Onli Linux Admin or Admin can update Tariff plan and delete IP address
			if ($admin_rights) {
				
				# if IP address is not set, ['userid'] = 0. The function will auto delete the rules for IP address.
				$new_ip_values['userid'] = (!empty($new_ip_values['ipaddress'])) ? $old_user_info['userid'] : 0;
				$new_ip_values['trafficid'] = $_POST['user']['trafficid'];
			}
			// Cashier can update only Tariff plan
			elseif ($cashier_rights) {
				
				$new_ip_values['userid'] = $old_user_info['userid'];
				$new_ip_values['trafficid'] = $_POST['user']['trafficid'];
			}
			// Technician not have rights to update
			else {
				
				$new_ip_values['userid'] = $old_user_info['userid'];
				$new_ip_values['trafficid'] = $old_ip_values['trafficid'];
			}
						
			# if checkbox is checked, free_mac is on, else free_mac does not exist
			$new_ip_values['free_mac'] = (!empty($new_ip_values['free_mac'])) ? 1 : 0;

			if ($USE_VLANS) {

				$ip_info = comparison_ip_vlan_changes($db, $old_ip_values, $new_ip_values);
			}
			else {
				$ip_info = comparison_ip_changes($db, $old_ip_values, $new_ip_values);
			}
						
			# Apply changes
			if (!empty($ip_info['id']) && !empty($ip_info['ipaddress'])) {
			
				$sql = 'UPDATE `static_ippool` SET userid = :userid, trafficid = :trafficid, vlan = :vlan, mac = :mac, free_mac = :free_mac, 
						name = :name, notes = :notes WHERE id = :id AND ipaddress = :ipaddress';
				$sth = $db->dbh->prepare($sql);
				$sth->bindParam(':userid', $ip_info['userid'], PDO::PARAM_INT);
				$sth->bindParam(':trafficid', $ip_info['trafficid'], PDO::PARAM_INT);
				$sth->bindParam(':vlan', $ip_info['vlan'], PDO::PARAM_STR);
				$sth->bindParam(':mac', $ip_info['mac'], PDO::PARAM_STR);
				$sth->bindParam(':free_mac', $ip_info['free_mac'], PDO::PARAM_INT);
				$sth->bindParam(':name', $ip_info['name'], PDO::PARAM_STR);
				$sth->bindParam(':notes', $new_ip[$i]['notes'], PDO::PARAM_STR);
				$sth->bindValue(':id', $ip_info['id'], PDO::PARAM_INT);
				$sth->bindParam(':ipaddress', $ip_info['ipaddress'], PDO::PARAM_STR);
				$sth->execute();
			}

			// If not have changes for IP, the function comparison_ip_* return only $ip_info['msg'] and nothing not be saved
			// Operator maybe is changed only the notes
			if (empty($ip_info['id']) && ($old_ip[$i]['notes'] != $new_ip[$i]['notes'])) {
				
				$sql = 'UPDATE `static_ippool` SET notes = :notes WHERE ipaddress = :ipaddress';
				$sth = $db->dbh->prepare($sql);
				$sth->bindParam(':notes', $new_ip[$i]['notes'], PDO::PARAM_STR);
				$sth->bindParam(':ipaddress', $old_ip[$i]['ipaddress'], PDO::PARAM_STR);
				$sth->execute();
			}

			// Comment if you not want to show info for IP changes
			$_SESSION['msg'] .= !empty($ip_info['msg']) ? "{$ip_info['msg']} <br>" : '';
		}
	}

#####################################################
	// Save new IP address
#####################################################

	if (!empty($_POST['new_ip']['ipaddress'])) {
		
		$ip = $_POST['new_ip']['ipaddress'];

		// GET infor for new IP address
		$sql = 'SELECT * FROM static_ippool WHERE ipaddress = :ipaddress';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':ipaddress', $ip, PDO::PARAM_STR);
		$sth->execute();
		$old_ip_values = $sth->fetch(PDO::FETCH_ASSOC);

		$new_ip_values = $_POST['new_ip'];
		$new_ip_values['userid'] = $old_user_info['userid'];
		$new_ip_values['trafficid'] = $new_user_info['trafficid'];
		$new_ip_values['name'] = $new_user_info['name'];
		
		# if checkbox is checked, free_mac is on, else free_mac does not exist
		$new_ip_values['free_mac'] = (!empty($new_ip_values['free_mac'])) ? 1 : 0;

		if ($USE_VLANS) {

			$ip_info = comparison_ip_vlan_changes($db, $old_ip_values, $new_ip_values);
		}
		else {
			$ip_info = comparison_ip_changes($db, $old_ip_values, $new_ip_values);
		}

		# Apply changes
		if (!empty($ip_info['id']) && !empty($ip_info['ipaddress'])) {
			
			$sql = 'UPDATE `static_ippool` SET userid = :userid, trafficid = :trafficid, vlan = :vlan, mac = :mac, free_mac = :free_mac, 
					name = :name, notes = :notes WHERE id = :id AND ipaddress = :ipaddress';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(':userid', $ip_info['userid'], PDO::PARAM_INT);
			$sth->bindParam(':trafficid', $ip_info['trafficid'], PDO::PARAM_INT);
			$sth->bindParam(':vlan', $ip_info['vlan'], PDO::PARAM_STR);
			$sth->bindParam(':mac', $ip_info['mac'], PDO::PARAM_STR);
			$sth->bindParam(':free_mac', $ip_info['free_mac'], PDO::PARAM_INT);
			$sth->bindParam(':name', $ip_info['name'], PDO::PARAM_STR);
			$sth->bindParam(':notes', $new_ip_values['notes'], PDO::PARAM_STR);
			$sth->bindValue(':id', $ip_info['id'], PDO::PARAM_INT);
			$sth->bindParam(':ipaddress', $ip_info['ipaddress'], PDO::PARAM_STR);
			$sth->execute();
		}

		// Comment if you not want to show info for new IP changes
		$_SESSION['msg'] .= !empty($ip_info['msg']) ? "{$ip_info['msg']} <br>" : '';

		// Start internet access
		if ($new_user_info['free_access'] == 1 || $new_user_info['expires'] > $data_now) {

			$result = shell_exec("$SUDO $IP rule del from $ip table EXPIRED 2>&1");
			$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', $ip).'<br>' : _s('Enabling internet access for IP address %s is failed', $ip).' - '.$result.'<br>';
		}
		// Stop internet access
		if ($new_user_info['free_access'] == 0 && $new_user_info['expires'] < $data_now) {

            if (empty($ip_rule_info[$ip])) {
                $result = shell_exec("$SUDO $IP rule add from $ip table EXPIRED 2>&1");
                $_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is stopped.', $ip).'<br>' : _s('Stopping internet access for IP address %s is failed', $ip).' - '.$result.'<br>';
            }
		}
	}

#####################################################
	// Comparison Freeradius
#####################################################

	if (!empty($_POST['freeradius'][0]['username']) && !empty($_POST['freeradius'][0]['value'])) {
		
		# !!! Do not change the order of check. The check for Username changes must be of end.!!!
		
		$old_fr = $_SESSION['radcheck'];
		$new_fr = $_POST['freeradius'];

		# Check for new attribute and insert
		if (!empty($_POST['attribute']) && !empty($_POST['op'])) {
			
			$variable = $_POST['attribute'];

			foreach ($variable as $key => $value) {
				
				if (!empty($value)) {
					
					$username = $old_fr[0]['username'];
					$attribute = $key;
					$op = $_POST['op'][$key];

					$sql = 'INSERT INTO `radcheck` ( `userid`, `username`, `attribute`, `op`, `value`)
							VALUES (:userid, :username, :attribute, :op, :value)';
					$sth = $db->dbh->prepare($sql);
					$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
					$sth->bindValue(':username', $username, PDO::PARAM_STR);
					$sth->bindValue(':attribute', $attribute, PDO::PARAM_STR);
					$sth->bindValue(':op', $op);
					$sth->bindValue(':value', strip_tags($value), PDO::PARAM_STR);
					$sth->execute();
				}
			}
		}

		# Check the additional attributes
		if (!empty($old_fr[4])) {
			
			for ($i=4; $i < count($old_fr); ++$i) { 
				
				if (!empty($new_fr[$i]['value']) && ($old_fr[$i]['value'] != $new_fr[$i]['value'] || $old_fr[$i]['op'] != $new_fr[$i]['op'])) {
					
					$id = $new_fr[$i]['id'];
					$op = $new_fr[$i]['op'];
					$value = strip_tags($new_fr[$i]['value']);

					$sql = 'UPDATE `radcheck` SET op = :op, value = :value WHERE id = :id AND userid = :userid';
					$sth = $db->dbh->prepare($sql);
					$sth->bindValue(':id', $id, PDO::PARAM_INT);
					$sth->bindValue(':op', $op);
					$sth->bindValue(':value', $value, PDO::PARAM_STR);
					$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
					$sth->execute();
				}
				if (empty($new_fr[$i]['value'])) {
					
					$id = $new_fr[$i]['id'];

					$sql = 'DELETE FROM `radcheck` WHERE id = :id';
					$sth = $db->dbh->prepare($sql);
					$sth->bindValue(':id', $id, PDO::PARAM_INT);
					$sth->execute();
				}
			}
		}

		# Calling-Station-Id (MAC address) is changed
		if ($old_fr[3]['value'] != $new_fr[3]['value']) {
			
			$id = $new_fr[3]['id'];
			$value = (empty($new_fr[3]['value']) || (!empty($new_fr[3]['value']) && IsValidMAC($new_fr[3]['value']))) ? $new_fr[3]['value'] : $old_fr[3]['value'];
            $_SESSION['msg'] .= ($value == $old_fr[3]['value']) ? _('Please enter valid MAC').'<br>' : '';

			$sql = 'UPDATE `radcheck` SET value = :value WHERE id = :id AND userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->bindValue(':value', $value, PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();
		}
			
		# Expiration is changed
		if (($old_fr[2]['value'] != $new_fr[2]['value']) && ($admin_rights)) {
			
			$id = $new_fr[2]['id'];
			$value = strip_tags($new_fr[2]['value']);

			$sql = 'UPDATE `radcheck` SET value = :value WHERE id = :id AND userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':id', $id, PDO::PARAM_INT);
			$sth->bindValue(':value', $value, PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();

			// Add audit
			add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "PPPoE Expiration is changed - ID: $userid, User: {$new_user_info['name']}, Username: {$old_fr[1]['username']}.", "{$old_fr[1]['value']}", "{$new_fr[1]['value']}");
		}

        # Simultaneous-Use is changed
        if ($old_fr[1]['value'] != $new_fr[1]['value']) {
            
            $id = $new_fr[1]['id'];
            settype($new_fr[1]['value'], "integer");

            $sql = 'UPDATE `radcheck` SET value = :value WHERE id = :id AND userid = :userid';
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':id', $id, PDO::PARAM_INT);
            $sth->bindValue(':value', $new_fr[1]['value'], PDO::PARAM_STR);
            $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
            $sth->execute();
        }

		# Freeradius group is changed
		if (($old_fr[0]['groupname'] != $new_fr[0]['groupname']) && ($admin_rights || $cashier_rights)) {
			
			$groupname = $new_fr[0]['groupname'];

			$sql = 'UPDATE `radusergroup` SET groupname = :groupname WHERE userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':groupname', $groupname, PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();

			// Add audit
			add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "PPPoE Group is changed - ID: $userid, User: {$new_user_info['name']}, Username: {$old_fr[1]['username']}.", "{$old_fr[0]['groupname']}", "{$new_fr[0]['groupname']}");
		}
		
		# Password is changed
		if ($old_fr[0]['value'] != $new_fr[0]['value']) {
			
			$password = strip_tags($new_fr[0]['value']);

			$sql = 'UPDATE `radcheck` SET value = :value WHERE attribute = :attribute AND userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':value', $password, PDO::PARAM_STR);
			$sth->bindValue(':attribute', 'Cleartext-Password', PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();

			// Add audit
			add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "PPPoE password is changed - ID: $userid, User: {$new_user_info['name']}, Username: {$old_fr[1]['username']}.", "{$old_fr[0]['value']}", "{$new_fr[0]['value']}");
		}
				
		# Username is changed
		if ($old_fr[0]['username'] != $new_fr[0]['username']) {
                
            $str = strip_tags($new_fr[0]['username']);
			$username = preg_replace('/\s+/', '_', $str);
			
			$sql = 'UPDATE `radcheck` SET username = :username WHERE userid = :userid';
			$db->dbh->beginTransaction();
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':username', $username, PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();

			$sql = 'UPDATE `payments` SET username = :username WHERE userid = :userid';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':username', $username, PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();

			$db->dbh->commit();

			// Add audit
			add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "PPPoE username is changed - ID: $userid, User: {$new_user_info['name']}, Username: {$old_fr[1]['username']}.", "{$old_fr[1]['username']}", "{$new_fr[0]['username']}");

            # if PPPoE session, kill
            if (count($_POST['freeradius']['framedipaddress']) > 0) {

                foreach ($_POST['freeradius']['framedipaddress'] as $value) {
                
                    if ($value) {

                        $cmd = "$SUDO $PYTHON $IMSLU_SCRIPTS/pppd_kill.py $value 2>&1";
                        $result = shell_exec($cmd);

                        $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                    }
                }
            }
		}
	}

	
#####################################################
	// Save new PPPoE account
#####################################################

	if (!empty($_POST['use_pppoe']) && (!empty($_POST['pppoe']['username']) && !empty($_POST['pppoe']['password']))) {

		$str = strip_tags($_POST['pppoe']['username']);
        $username = preg_replace('/\s+/', '_', $str);
		$password = strip_tags($_POST['pppoe']['password']);
		$groupname = $_POST['groupname'];

		$sql = 'INSERT INTO `radcheck` ( `userid`, `username`, `attribute`, `op`, `value`)
				VALUES (:userid, :username, :attribute, :op, :value)';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':attribute', 'Cleartext-Password');
		$sth->bindValue(':op', ':=');
		$sth->bindValue(':value', $password, PDO::PARAM_STR);
		$sth->execute();

        $sql = 'INSERT INTO `radcheck` ( `userid`, `username`, `attribute`, `op`, `value`)
                VALUES (:userid, :username, :attribute, :op, :value)';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid);
        $sth->bindValue(':username', $username, PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Simultaneous-Use');
        $sth->bindValue(':op', ':=');
        $sth->bindValue(':value', 1, PDO::PARAM_STR);
        $sth->execute();

		$sql = 'INSERT INTO `radcheck` (`userid`, `username`, `attribute`, `op`)
				VALUES (:userid, :username, :attribute, :op)';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':attribute', 'Expiration');
		$sth->bindValue(':op', ':=');
		$sth->execute();
		
		$sql = 'INSERT INTO `radcheck` (`userid`, `username`, `attribute`, `op`)
				VALUES (:userid, :username, :attribute, :op)';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':attribute', 'Calling-Station-Id');
		$sth->bindValue(':op', ':=');
		$sth->execute();

		$sql = 'INSERT INTO `radusergroup` (`userid`, `username`, `groupname`)
				VALUES (:userid, :username, :groupname)';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->bindValue(':username', $username, PDO::PARAM_STR);
		$sth->bindValue(':groupname', $groupname, PDO::PARAM_STR);
		$sth->execute();

		$sql = 'UPDATE `users` SET pppoe = :pppoe WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':pppoe', 1, PDO::PARAM_INT);
		$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();

		if ($new_user_info['free_access'] == 0) {

			$sql = 'UPDATE `payments` SET username = :username
					WHERE userid = :userid ORDER BY expires DESC';
			$sth = $db->dbh->prepare($sql);
			$sth->bindValue(':username', $username, PDO::PARAM_STR);
			$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
			$sth->execute();
		}
		
		$db->dbh->commit();

		// Add audit
		add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_USER, "PPPoE account is created - ID: $userid, User: {$new_user_info['name']}.", null, "Username: $username, Password: $password");
	}

	header("Location: user_edit.php?userid={$old_user_info['userid']}");
}
?>
