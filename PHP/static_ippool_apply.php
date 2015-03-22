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

// enable debug mode
 error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$check->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

if ($_SESSION['form_key'] !== $_POST['form_key']) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';
require_once dirname(__FILE__).'/include/network.php';

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();

###################################################################################################
	// Delete IP address
###################################################################################################

	if (!empty($_POST['action']) && $_POST['action'] == 'delete' && !empty($_POST['id'])) {
		
		$id = $_POST['id'];

		$sql = 'DELETE FROM `static_ippool` WHERE id = :id AND userid = :userid';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':id', $value, PDO::PARAM_INT);
		$sth->bindValue(':userid', 0, PDO::PARAM_INT);

		foreach ($id as $value) {

			$sth->execute();
		}

		$db->dbh->commit();

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_STATIC_IP, "IP addresses are deleted.", "ID - ".implode(',', $id));

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: static_ippool.php");
        exit;
	}

###################################################################################################
	// Update pool name 
###################################################################################################

	if (isset($_POST['change_pool_name'])) {
		
		if (empty($_POST['pool_name'])) {
			
			$msg['msg_pool_name'] = _('Name cannot empty. Re-select IP addresses!');
			show_error_message('action', 'change_pool_name', 'id', $msg, 'static_ippool.php');
			exit;
		}
		$pool_name = $_POST['pool_name'];
		
		$sql = 'UPDATE `static_ippool` SET pool_name = :pool_name WHERE id = :id';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':pool_name', $pool_name, PDO::PARAM_STR);
		$sth->bindParam(':id', $value, PDO::PARAM_INT);

		$id = unserialize($_POST['id']);

		foreach ($id as $value) {

			$sth->execute();
		}

		$db->dbh->commit();

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_STATIC_IP, "pool name is changed.");

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
		header("Location: static_ippool.php");
		exit;
	}

###################################################################################################
	// Update network type
###################################################################################################

	if (isset($_POST['change_network_type'])) {
		
		$network_type = $_POST['network_type'];

		$sql = 'UPDATE `static_ippool` SET network_type = :network_type WHERE id = :id';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':network_type', $network_type, PDO::PARAM_STR);
		$sth->bindParam(':id', $value, PDO::PARAM_INT);

		$id = unserialize($_POST['id']);

		foreach ($id as $value) {

			$sth->execute();
		}

		$db->dbh->commit();

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_STATIC_IP, "Netrowk Type is changed.");

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
		unset($_POST);
		header("Location: static_ippool.php");
		exit;
	}

###################################################################################################
	// Save changes for IP Address
###################################################################################################

	if (!empty($_POST['change_ip_values']) && is_array($_POST['edit_ip'])) {
	
		require_once dirname(__FILE__).'/include/network.php';

		$name = unserialize($_POST['edit_ip']['id_name']);

#####################################################
	// Comparison changes
#####################################################

		$old_ip_values = $_SESSION['old_ip_info'];
		$new_ip_values = $_POST['edit_ip'];

		$new_ip_values['name'] = (empty($new_ip_values['name'])) ? "{$name[$new_ip_values['userid']]}" : "{$new_ip_values['name']}";

		if ($USE_VLANS) {
			$ip_info = comparison_ip_vlan_changes($db, $old_ip_values, $new_ip_values);
		}
		else {
			$ip_info = comparison_ip_changes($db, $old_ip_values, $new_ip_values);
		}

		if (!empty($ip_info['id']) && !empty($ip_info['ipaddress'])) {
			
			$sql = 'UPDATE `static_ippool` SET userid = :userid, trafficid = :trafficid, vlan = :vlan, mac = :mac, free_mac = :free_mac, 
					pool_name = :pool_name, network_type = :network_type, name = :name, notes = :notes WHERE id = :id AND ipaddress = :ipaddress';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(':userid', $ip_info['userid'], PDO::PARAM_INT);
			$sth->bindParam(':trafficid', $ip_info['trafficid'], PDO::PARAM_INT);
			$sth->bindParam(':vlan', $ip_info['vlan'], PDO::PARAM_STR);
			$sth->bindParam(':mac', $ip_info['mac'], PDO::PARAM_STR);
			$sth->bindParam(':free_mac', $ip_info['free_mac'], PDO::PARAM_INT);
			$sth->bindParam(':pool_name', $_POST['edit_ip']['pool_name'], PDO::PARAM_STR);
			$sth->bindParam(':network_type', $_POST['edit_ip']['network_type'], PDO::PARAM_STR);
			$sth->bindParam(':name', $ip_info['name'], PDO::PARAM_STR);
			$sth->bindParam(':notes', $_POST['edit_ip']['notes'], PDO::PARAM_STR);
			$sth->bindValue(':id', $ip_info['id'], PDO::PARAM_INT);
			$sth->bindParam(':ipaddress', $ip_info['ipaddress'], PDO::PARAM_STR);
			$sth->execute();
		}

		if (empty($ip_info['id']) && ($old_ip_values['notes'] != $new_ip_values['notes'])) {
				
			$sql = 'UPDATE `static_ippool` SET notes = :notes WHERE ipaddress = :ipaddress';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(':notes', $new_ip_values['notes'], PDO::PARAM_STR);
			$sth->bindParam(':ipaddress', $old_ip_values['ipaddress'], PDO::PARAM_STR);
			$sth->execute();
		}

		$_SESSION['msg'] .= isset($ip_info['msg']) ? "{$ip_info['msg']} <br>" : '';
		unset($_POST);
		header("Location: static_ippool.php");
		exit;
	}

###################################################################################################
	// Save new SQLIPPOOL
###################################################################################################

	if (isset($_POST['save_static_ippool'])) {
		
		$network_type = $_POST['network_type'];

		if (empty($_POST['pool_name'])) {
			
			$msg['msg_pool_name'] = _('Name cannot empty.');
			show_error_message('action', 'addippool', null, $msg, 'static_ippool.php');
			exit;
		}
		$pool_name = $_POST['pool_name'];

		if (empty($_POST['ipaddress_start'])) {
			
			$msg['msg_ipaddress_start'] = _('IP address cannot empty.');
			show_error_message('action', 'addippool', null, $msg, 'static_ippool.php');
			exit;
		}
		$ip_start = $_POST['ipaddress_start'];

		if (!filter_var($ip_start, FILTER_VALIDATE_IP)) {
			
			$msg['msg_ipaddress_start'] = _('Not a valid IP address!');
			show_error_message('action', 'addippool', null, $msg, 'static_ippool.php');
			exit;
		}
		
		// Check and insert in database IP address range
		if (!empty($_POST['ipaddress_end'])) {
			
			$ip_end = $_POST['ipaddress_end'];

			if (!filter_var($ip_end, FILTER_VALIDATE_IP)) {
				
				$msg['msg_ipaddress_end'] = _('Not a valid IP address!');
				show_error_message('action', 'addippool', null, $msg, 'static_ippool.php');
				exit;
			}
			
			$ip_start = ip2long($ip_start);
			$ip_end = ip2long($ip_end);

			if ($ip_start > $ip_end) {
				
				$msg['msg_ipaddress_start'] = _('Start IP address must be less!');
				$msg['msg_ipaddress_end'] = _('End IP address must be larger!');
				show_error_message('action', 'addippool', null, $msg, 'static_ippool.php');
				exit;
			}
			
			for ($i = $ip_start; $i <= $ip_end; ++$i) {
				
				$ip[$i] = long2ip($i);
			}

			$sql = 'INSERT INTO `static_ippool` (`pool_name`,`ipaddress`,`network_type`) VALUES (:pool_name, :ip, :network_type)';
			$db->dbh->beginTransaction();
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(':pool_name', $pool_name, PDO::PARAM_STR);
			$sth->bindParam(':ip', $value);
			$sth->bindParam(':network_type', $network_type, PDO::PARAM_INT);
			
			foreach ($ip as $value) {

				$sth->execute();
			}
	
			$db->dbh->commit();

			unset($ip);
		}
		else {
			// Insert IP address in database
			$sql = 'INSERT INTO `static_ippool` (`pool_name`,`ipaddress`,`network_type`) VALUES (?, ?, ?)';
			$sth = $db->dbh->prepare($sql);
			$sth->bindParam(1, $pool_name);
			$sth->bindParam(2, $ip_start);
			$sth->bindParam(3, $network_type);
			$sth->execute();

			unset($ip_start);
		}

		$msg = !empty($_POST['ipaddress_end']) ? _s('IP address range %s - %s is added successfully.', $_POST['ipaddress_start'], $_POST['ipaddress_end'])."<br>" : _s('IP address %s is added successfully.', $_POST['ipaddress_start'])."<br>";
		
		// Add audit
		add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_STATIC_IP, "IP addresses are added successfully.", null, $msg);

		$_SESSION['msg'] .=  $msg;
		unset($_POST);
		header("Location: static_ippool.php");
		exit;
	}


  if (isset($_POST['action'])) {

#####################################################
    // Display messages
#####################################################
    echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $_network_type = array('public' => _('public'), 'private' => _('private'));

###################################################################################################
    // PAGE HEADER
###################################################################################################

    $page['title'] = 'Static IP addresses apply';
    $page['file'] = 'static_ippool_apply.php';

    require_once dirname(__FILE__).'/include/page_header.php';

###################################################################################################
    // Change pool name 
###################################################################################################

    if (!empty($_POST['action']) && $_POST['action'] == 'change_pool_name' && !empty($_POST['id'])) {

        $form =
"    <form action=\"static_ippool_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('Change pool name for IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"pool_name\">";
        $form .= (isset($_POST['msg_pool_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_pool_name']}</span>" : '';
        $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value='".serialize($_POST['id'])."'>
              <input type=\"submit\" name=\"change_pool_name\" value=\""._('save')."\">
            </td>
          </tr>     
        </tbody>
      </table>
    </form>\n";

        echo $form;
    }           

###################################################################################################
    // Change network type
###################################################################################################

    if (!empty($_POST['action']) && $_POST['action'] == 'change_network_type' && !empty($_POST['id'])) {

        $form =
"    <form action=\"static_ippool_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('Change network type for IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('change network type')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'network_type', null, $_network_type)."
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value='".serialize($_POST['id'])."'>
              <input type=\"submit\" name=\"change_network_type\" value=\""._('save')."\">
            </td>
          </tr>     
        </tbody>
      </table>
    </form>\n";

        echo $form;
    }           

###################################################################################################
    // Edit IP Address
################################################################################################### 

    if (empty($_POST['action']) && !empty($_POST['id'])) {

        $id = $_POST['id'];

        $sql = 'SELECT * FROM static_ippool WHERE id = :id';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':id', $id, PDO::PARAM_INT);
        $sth->execute();
        $ip_info = $sth->fetch(PDO::FETCH_ASSOC);

        // Add in Session ip info for later comparison
        $_SESSION['old_ip_info'] = $ip_info;

        $sql = 'SELECT userid,name FROM users';
        $sth = $db->dbh->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {

            for ($i = 0; $i < count($rows); ++$i) {

            $user_id[$rows[$i]['userid']] = $rows[$i]['name'];
            }
            $userid = array(0 => '') + $user_id;
        }
        else {
            $userid = array(0 => '');
        }

        $sql = 'SELECT trafficid,name FROM traffic';
        $sth = $db->dbh->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {

            for ($i = 0; $i < count($rows); ++$i) {

            $traffic_id[$rows[$i]['trafficid']] = $rows[$i]['name'];
            }
            $trafficid = array(0 => '') + $traffic_id;
        }
        else {
            $trafficid = array(0 => '');
        }

        $free_mac = array(0 => _('no'), 1 => _('yes'));

        $form =
"    <form action=\"static_ippool_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('edit IP address').": {$ip_info['ipaddress']}</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'edit_ip[userid]', $ip_info['userid'], $userid)."
              <label style=\"color: red;\">";
        $form .= ($ip_info['userid'] != 0) ? _s('Mandatory, otherwise the rules for IP address %s will be deleted!', $ip_info['ipaddress']) : _s('Mandatory or rules for IP address %s will be ignored!', $ip_info['ipaddress']);
        $form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('service')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'edit_ip[trafficid]', $ip_info['trafficid'], $trafficid)."
              <label style=\"color: red;\">";
        $form .= ($ip_info['trafficid'] != 0) ? _s('Mandatory, otherwise the rules for IP address %s will be deleted!', $ip_info['ipaddress']) : _s('Mandatory or rules for IP address %s will be ignored!', $ip_info['ipaddress']);
        $form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>vlan</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"edit_ip[vlan]\" value=\"{$ip_info['vlan']}\">
              <label style=\"color: red;\">";
        $form .= ($USE_PPPoE == TRUE) ? _s('Mandatory or rules for IP address %s will be ignored!', $ip_info['ipaddress']) : '';
        $form .= "</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>mac</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"edit_ip[mac]\" value=\"{$ip_info['mac']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free mac')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'edit_ip[free_mac]', $ip_info['free_mac'], $free_mac)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"edit_ip[pool_name]\" value=\"{$ip_info['pool_name']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('network type')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"edit_ip[network_type]\" value=\"{$ip_info['network_type']}\">
            </td>
          </tr> 
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"edit_ip[name]\" value=\"".chars($ip_info['name'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"edit_ip[notes]\" cols=\"55\" rows=\"2\">".chars($ip_info['notes'])."</textarea>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('Warning, the changes will be applied directly!')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"edit_ip[id_name]\" value='".serialize($userid)."'>
              <input type=\"submit\" name=\"change_ip_values\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot>
      </table>
    </form>\n";

        echo $form;
    }

    require_once dirname(__FILE__).'/include/page_footer.php';
  }
    else {

	   header("Location: static_ippool.php");
    }
}
?>