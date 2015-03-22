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
 
/**
 * This function comparison changes for IP Address if $USE_VLANS = TRUE and applies the change with calling python scripts and functions
 * 
 * @param array $old_ip_values - ['id'], ['userid'], ['trafficid'], ['ipaddress'], [vlan], [mac], [free_mac]
 * @param array $new_ip_values - ['userid'], ['trafficid'], [vlan], [mac], [free_mac], ['name']
 */
function comparison_ip_vlan_changes($db, $old_ip_values, $new_ip_values) {

	global $SUDO, $PYTHON, $IMSLU_SCRIPTS;

	$sql = 'SELECT trafficid,local_in,local_out FROM traffic';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {

		$trafficid = array();

		for ($i = 0; $i < count($rows); ++$i) {

		$trafficid[$rows[$i]['trafficid']] = array('local_in' => $rows[$i]['local_in'], 'local_out' => $rows[$i]['local_out']);
		}
	}

	if (!is_array($old_ip_values) || !is_array($new_ip_values)) {
		
		$msg = 'The variables: $old_ip_values and $new_ip_values, must be array';
		return array('msg' => $msg);
	}

//echo '<pre>' .' IP OLD '; print_r($old_ip_values); echo '</pre>';
//echo '<pre>' .' IP NEW '; print_r($new_ip_values); echo '</pre>';

	# Nothing has changed.
	if ($old_ip_values['userid'] == 0 && ($new_ip_values['userid'] == 0 || $new_ip_values['trafficid'] == 0)) {
		
		$msg = _s('No changes were made to IP address %s.', $old_ip_values['ipaddress']);
		return array('msg' => $msg);
	}

	# 1. User and Tariff plan is != 0. Add new rules for IP Address
	if ($old_ip_values['userid'] == 0 && $new_ip_values['userid'] != 0 && $new_ip_values['trafficid'] != 0) {

		$vlan = isset($new_ip_values['vlan']) ? $new_ip_values['vlan'] : '';
		$mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.add_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '$vlan', mac = '$mac', free_mac = '{$new_ip_values['free_mac']}', donw_speed = '{$trafficid[$new_ip_values['trafficid']]['local_in']}', up_speed = '{$trafficid[$new_ip_values['trafficid']]['local_out']}')
END";
		shell_exec($command);

		// Add audit
		add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_USER, "IP Address {$old_ip_values['ipaddress']} is added to - ID: {$new_ip_values['userid']}, User: {$new_ip_values['name']}.");

		$msg = (!empty($mac) || empty($new_ip_values['mac'])) ? _s('The new rules for IP address %s are added.', $old_ip_values['ipaddress']) : _s('The new rules for IP address %s are added. Please enter valid MAC.', $old_ip_values['ipaddress']);
		$update = array ('id' => $old_ip_values['id'], 'userid' => $new_ip_values['userid'], 'trafficid' => $new_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => $vlan, 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);
		return $update;
	}

	# 2. User or Tariff plan is 0. Clear all rules for IP Address
	if ($old_ip_values['userid'] != 0 && ($new_ip_values['userid'] == 0 || $new_ip_values['trafficid'] == 0)) {
		
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '{$old_ip_values['mac']}')
END";
		shell_exec($command);

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, "IP Address {$old_ip_values['ipaddress']} is deleted from - ID: {$old_ip_values['userid']}, User: {$new_ip_values['name']}.");

		$msg = _s('All rules for IP address %s are deleted.', $old_ip_values['ipaddress'])." - line 105";
		$update = array ('id' => $old_ip_values['id'], 'userid' => 0, 'trafficid' => 0, 'ipaddress' => $old_ip_values['ipaddress'], 
						 'vlan' => '', 'mac' => '', 'free_mac' => 0, 'name' => '', 'msg' => $msg);

		return $update;
	}

	# 3. Any changes are possible	
	if ($old_ip_values['userid'] != 0 && $new_ip_values['userid'] != 0 && $new_ip_values['trafficid'] != 0) {
		
		# 3.1 IP Address is bound to the same user and the same tariff plan.
		if (($old_ip_values['userid'] == $new_ip_values['userid']) && ($old_ip_values['trafficid'] == $new_ip_values['trafficid'])) {

			# 3.1.1 Nothing has changed.
			if (($old_ip_values['vlan'] == $new_ip_values['vlan']) && ($old_ip_values['mac'] == $new_ip_values['mac']) && ($old_ip_values['free_mac'] == $new_ip_values['free_mac'])) {
				
				$msg = _s('No changes were made to IP address %s.', $old_ip_values['ipaddress']);
				return array('msg' => $msg);
			}

			# 3.1.2 VLAN is same, but (MAC or Free MAC) are changed
			elseif ((($old_ip_values['vlan'] == $new_ip_values['vlan']) && !empty($new_ip_values['vlan'])) && (($old_ip_values['mac'] != $new_ip_values['mac']) || ($old_ip_values['free_mac'] != $new_ip_values['free_mac']))) {
				
				# 3.1.2.1 MAC is changed and Free MAC - No
				if (!empty($new_ip_values['mac']) && $new_ip_values['free_mac'] == 0) {

					$mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.replace_mac(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '$mac')
END";
					shell_exec($command);

					$msg = !empty($mac) ? _s('MAC for IP address %s are updated.', $old_ip_values['ipaddress']) : _s('Please enter valid MAC for IP address %s.', $old_ip_values['ipaddress']);
					$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => $old_ip_values['vlan'], 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
				}
				# 3.1.2.2 MAC is empty or Free MAC -Yes
				elseif (empty($new_ip_values['mac']) || $new_ip_values['free_mac'] == 1) {
						
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_mac(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '{$old_ip_values['mac']}')
END";
					shell_exec($command);

					$msg = (empty($new_ip_values['mac']) && ($new_ip_values['free_mac'] == 0)) ? _s('MAC for IP address %s are deleted.', $old_ip_values['ipaddress']) : _s('IP address %s is free from MAC.', $old_ip_values['ipaddress']);
					$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => $old_ip_values['vlan'], 'mac' => $new_ip_values['mac'], 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
				}
			}

			# 3.1.3 VLAN is changed
			elseif (($old_ip_values['vlan'] != $new_ip_values['vlan']) && !empty($new_ip_values['vlan'])) {
					
				$mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';
					
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '{$old_ip_values['mac']}')
END";
					shell_exec($command);

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.add_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '{$new_ip_values['vlan']}', mac = '$mac', free_mac = '{$new_ip_values['free_mac']}', donw_speed = '{$trafficid[$new_ip_values['trafficid']]['local_in']}', up_speed = '{$trafficid[$new_ip_values['trafficid']]['local_out']}')
END";
					shell_exec($command);

				$msg = (!empty($mac) || empty($new_ip_values['mac'])) ? _s('The new VLAN and MAC for IP address %s are added.', $old_ip_values['ipaddress'])." - line 193" : _s('The new VLAN for IP address %s are added. Please enter valid MAC.', $old_ip_values['ipaddress'])." - line 193";
				$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => $new_ip_values['vlan'], 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
			}
			
			# 3.1.4 VLAN is deleted
			elseif (($old_ip_values['vlan'] != $new_ip_values['vlan']) && empty($new_ip_values['vlan'])) {

                $mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '{$old_ip_values['mac']}')
END";
                shell_exec($command);

                $msg = _s('VLAN and MAC for IP address %s are deleted.', $old_ip_values['ipaddress'])." - line 215";
                $update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
                                'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => '', 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'],
                                'name' => $new_ip_values['name'], 'msg' => $msg);

                return $update;
			}
			
			# 3.1.5 VLAN is not set.
			elseif (empty($old_ip_values['vlan']) && empty($new_ip_values['vlan'])) {
						
				# 3.1.5.1 Adding (MAC or Free MAC) or deleting (MAC or Free MAC) in DB
				if (($old_ip_values['mac'] != $new_ip_values['mac']) || ($old_ip_values['free_mac'] != $new_ip_values['free_mac'])) {
						
					$mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';

					$msg = (!empty($mac) || empty($new_ip_values['mac'])) ? _s('MAC and Free MAC for IP address %s are updated.', $old_ip_values['ipaddress'])." - line 231" : _s('Please enter valid MAC for IP address %s.', $old_ip_values['ipaddress'])." - line 231";
					$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => '', 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

					return $update;
				}
			}
		}

		# 3.2 Tariff plan is changed
		if ($old_ip_values['trafficid'] != $new_ip_values['trafficid']) {
			
			# 3.2.1 Only tariff plan is changed
			if (($old_ip_values['trafficid'] != $new_ip_values['trafficid']) && ($old_ip_values['vlan'] == $new_ip_values['vlan']) && ($old_ip_values['mac'] == $new_ip_values['mac']) && ($old_ip_values['free_mac'] == $new_ip_values['free_mac'])){
				
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '{$old_ip_values['mac']}')
END";
					shell_exec($command);

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.add_ip_rules(ip = '{$old_ip_values['ipaddress']}', vlan = '{$old_ip_values['vlan']}', mac = '{$old_ip_values['mac']}', free_mac = '{$old_ip_values['free_mac']}', donw_speed = '{$trafficid[$new_ip_values['trafficid']]['local_in']}', up_speed = '{$trafficid[$new_ip_values['trafficid']]['local_out']}')
END";
				shell_exec($command);

				$msg = _s('The tariff plan for IP address %s is changed.', $old_ip_values['ipaddress']);
				$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $new_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => $old_ip_values['vlan'], 'mac' => $old_ip_values['mac'], 'free_mac' => $old_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
			}
			# 3.2.2 Any changes are possible
			else {
				
				$msg = _s('No changes were made to IP address %s. Before change something, first change tariff plan.', $old_ip_values['ipaddress']);
				return array('msg' => $msg);
			}
		}

		# 3.3 User is replaced with another user
		if ($old_ip_values['userid'] != $new_ip_values['userid']) {
				
			$msg = _s('No changes were made to IP address %s. First exempt the IP address from the user to which is added.', $old_ip_values['ipaddress']);
			return array('msg' => $msg);
		}
	}

	$msg = _('The system does not support the changes, who want you to apply! The function comparison_ip_vlan_changes() in network.inc.php need to develop.');
	return array('msg' => $msg);
}

/**
 * This function comparison changes for IP Address if $USE_VLANS = FALSE and applies the change with calling python scripts and functions
 * 
 * @param array $old_ip_values - ['id'], ['userid'], ['trafficid'], ['ipaddress'], [mac], [free_mac]
 * @param array $new_ip_values - ['userid'], ['trafficid'], [mac], [free_mac], ['name']
 */
function comparison_ip_changes($db, $old_ip_values, $new_ip_values) {

	global $SUDO, $PYTHON, $IMSLU_SCRIPTS;

	$sql = 'SELECT trafficid,local_in,local_out FROM traffic';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {

		$trafficid = array();

		for ($i = 0; $i < count($rows); ++$i) {

		$trafficid[$rows[$i]['trafficid']] = array('local_in' => $rows[$i]['local_in'], 'local_out' => $rows[$i]['local_out']);
		}
	}

	if (!is_array($old_ip_values) || !is_array($new_ip_values)) {
		
		$msg = 'The variables: $old_ip_values and $new_ip_values, must be array';
		return array('msg' => $msg);
	}

//echo '<pre>' .' IP OLD '; print_r($old_ip_values); echo '</pre>';
//echo '<pre>' .' IP NEW '; print_r($new_ip_values); echo '</pre>';

	# Nothing has changed.
	if ($old_ip_values['userid'] == 0 && ($new_ip_values['userid'] == 0 || $new_ip_values['trafficid'] == 0)) {
		
		$msg = _s('No changes were made to IP address %s.', $old_ip_values['ipaddress']);
		return array('msg' => $msg);
	}

	# 1. User and Tariff plan is != 0. Add The new rules for IP Address
	if ($old_ip_values['userid'] == 0 && $new_ip_values['userid'] != 0 && $new_ip_values['trafficid'] != 0) {

		$mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.add_ip_rules(ip = '{$old_ip_values['ipaddress']}', mac = '$mac', free_mac = '{$new_ip_values['free_mac']}', donw_speed = '{$trafficid[$new_ip_values['trafficid']]['local_in']}', up_speed = '{$trafficid[$new_ip_values['trafficid']]['local_out']}')
END";
		shell_exec($command);

		// Add audit
		add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_USER, "IP Address {$old_ip_values['ipaddress']} is added to - ID: {$new_ip_values['userid']}, User: {$new_ip_values['name']}.");

		$msg = (!empty($mac) || empty($new_ip_values['mac'])) ? _s('The new rules for IP address %s are added.', $old_ip_values['ipaddress']) : _s('The new rules for IP address %s are added. Please enter valid MAC.', $old_ip_values['ipaddress']);
		$update = array ('id' => $old_ip_values['id'], 'userid' => $new_ip_values['userid'], 'trafficid' => $new_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => '', 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);
		return $update;
	}

	# 2. User or Tariff plan is 0. Clear all rules for IP Address
	if ($old_ip_values['userid'] != 0 && ($new_ip_values['userid'] == 0 || $new_ip_values['trafficid'] == 0)) {
		
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_ip_rules(ip = '{$old_ip_values['ipaddress']}', mac = '{$old_ip_values['mac']}')
END";
		shell_exec($command);

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, "IP Address {$old_ip_values['ipaddress']} is deleted from - ID: {$old_ip_values['userid']}, User: {$new_ip_values['name']}.");

		$msg = _s('All rules for IP address %s are deleted.', $old_ip_values['ipaddress']);
		$update = array ('id' => $old_ip_values['id'], 'userid' => 0, 'trafficid' => 0, 'ipaddress' => $old_ip_values['ipaddress'], 
						 'vlan' => '', 'mac' => '', 'free_mac' => 0, 'name' => '', 'msg' => $msg);

		return $update;
	}

	# 3. Any changes are possible	
	if ($old_ip_values['userid'] != 0 && $new_ip_values['userid'] != 0 && $new_ip_values['trafficid'] != 0) {
		
		# 3.1 IP Address is bound to the same user and the same tariff plan.
		if (($old_ip_values['userid'] == $new_ip_values['userid']) && ($old_ip_values['trafficid'] == $new_ip_values['trafficid'])) {

			# 3.1.1 Nothing has changed.
			if (($old_ip_values['mac'] == $new_ip_values['mac']) && ($old_ip_values['free_mac'] == $new_ip_values['free_mac'])) {
				
				$msg = _s('No changes were made to IP address %s.', $old_ip_values['ipaddress']);
				return array('msg' => $msg);
			}

			# 3.1.2 MAC or Free MAC are changed
			elseif (($old_ip_values['mac'] != $new_ip_values['mac']) || ($old_ip_values['free_mac'] != $new_ip_values['free_mac'])) {
				
				# 3.1.2.1 MAC is changed and Free MAC - No
				if (!empty($new_ip_values['mac']) && $new_ip_values['free_mac'] == 0) {

					$mac = IsValidMAC($new_ip_values['mac']) ? $new_ip_values['mac'] : '';

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.replace_mac(ip = '{$old_ip_values['ipaddress']}', mac = '$mac')
END";
					shell_exec($command);

					$msg = !empty($mac) ? _s('MAC for IP address %s are updated.', $old_ip_values['ipaddress']) : _s('Please enter valid MAC for IP address %s.', $old_ip_values['ipaddress']);
					$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => '', 'mac' => $mac, 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
				}
				# 3.1.2.2 MAC is empty or Free MAC -Yes
				elseif (empty($new_ip_values['mac']) || $new_ip_values['free_mac'] == 1) {
						
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_mac(ip = '{$old_ip_values['ipaddress']}', mac = '{$old_ip_values['mac']}')
END";
					shell_exec($command);

					$msg = (empty($new_ip_values['mac']) && ($new_ip_values['free_mac'] == 0)) ? _s('MAC for IP address %s are deleted.', $old_ip_values['ipaddress']) : _s('IP address %s is free from MAC.', $old_ip_values['ipaddress']);
					$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $old_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => '', 'mac' => $new_ip_values['mac'], 'free_mac' => $new_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
				}
			}
		}

		# 3.2 Tariff plan is changed
		if ($old_ip_values['trafficid'] != $new_ip_values['trafficid']) {
			
			# 3.2.1 Only tariff plan is changed
			if (($old_ip_values['trafficid'] != $new_ip_values['trafficid']) && ($old_ip_values['mac'] == $new_ip_values['mac']) && ($old_ip_values['free_mac'] == $new_ip_values['free_mac'])){
				
# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.del_ip_rules(ip = '{$old_ip_values['ipaddress']}', mac = '{$old_ip_values['mac']}')
END";
					shell_exec($command);

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.add_ip_rules(ip = '{$old_ip_values['ipaddress']}', mac = '{$old_ip_values['mac']}', free_mac = '{$old_ip_values['free_mac']}', donw_speed = '{$trafficid[$new_ip_values['trafficid']]['local_in']}', up_speed = '{$trafficid[$new_ip_values['trafficid']]['local_out']}')
END";
				shell_exec($command);

				$msg = _s('The tariff plan for IP address %s is changed.', $old_ip_values['ipaddress']);
				$update = array ('id' => $old_ip_values['id'], 'userid' => $old_ip_values['userid'], 'trafficid' => $new_ip_values['trafficid'], 
						 'ipaddress' => $old_ip_values['ipaddress'], 'vlan' => '', 'mac' => $old_ip_values['mac'], 'free_mac' => $old_ip_values['free_mac'], 
						 'name' => $new_ip_values['name'], 'msg' => $msg);

				return $update;
			}
			# 3.2.2 Any changes are possible
			else {
				
				$msg = _s('No changes were made to IP address %s. Before change something, first change tariff plan.', $old_ip_values['ipaddress']);
				return array('msg' => $msg);
			}
		}

		# 3.3 User is replaced with another user
		if ($old_ip_values['userid'] != $new_ip_values['userid']) {
				
			$msg = _s('No changes were made to IP address %s. First exempt the IP address from the user to which is added.', $old_ip_values['ipaddress']);
			return array('msg' => $msg);
		}
	}

	$msg = _('The system does not support the changes, who want you to apply! The function comparison_ip_changes() in network.inc.php need to develop.');
	return array('msg' => $msg);
}

?>
