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

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

###################################################################################################
    // Save new User
###################################################################################################

if (!empty($_POST['savenew'])) {
        
    if(empty($_POST['user_name'])) {

        $msg['msg_user_name'] = _('Name cannot empty.');
        show_error_message(null, null, null, $msg, 'user_new.php');
    exit;
    }

    if(empty($_POST['ip']) && empty($_POST['use_pppoe'])) {

        $msg['msg_ip'] = _('Select IP address or use PPPoE.');
        $msg['msg_use_pppoe'] = _('Use PPPoE or select IP address.');
        show_error_message(null, null, null, $msg, 'user_new.php');
    exit;
    }

    if(!empty($_POST['use_pppoe']) && (empty($_POST['pppoe']['username']) || empty($_POST['pppoe']['password']))) {

        $msg['msg_use_pppoe'] = _('Enter PPPoE username and password.');
        show_error_message(null, null, null, $msg, 'user_new.php');
    exit;
    }

    $name = strip_tags($_POST['user_name']);
    $locationid = $_POST['locationid'];
    $address = strip_tags($_POST['address']);
    $phone_number = strip_tags($_POST['phone_number']);
    $notes = $_POST['notes'];
    $created = date('Y-m-d H:i:s');
    $tariff_plan = $_POST['trafficid'];

    $sql = 'INSERT INTO `users` (`name`, `locationid`, `address`, `phone_number`, `notes`, `created`, `trafficid`, `pay`, `free_access`, `not_excluding`, `switchid`, `pppoe`)
            VALUES (:name, :locationid, :address, :phone_number, :notes, :created, :tariff_plan, :pay, :free_access, :not_excluding, :switchid, :pppoe)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':name', $name, PDO::PARAM_STR);
    $sth->bindValue(':locationid', $locationid, PDO::PARAM_INT);
    $sth->bindValue(':address', $address, PDO::PARAM_STR);
    $sth->bindValue(':phone_number', $phone_number, PDO::PARAM_INT);
    $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
    $sth->bindValue(':created', $created);
    $sth->bindValue(':tariff_plan', $tariff_plan, PDO::PARAM_INT);

    if (!empty($_POST['pay']) && $admin_permissions) {
        $pay = $_POST['pay'];
        $sth->bindValue(':pay', $pay, PDO::PARAM_INT);
    }
    else {
        $pay = 0.00;
        $sth->bindValue(':pay', 0.00);
    }

    if (!empty($_POST['free_access']) && $admin_permissions) {
        $sth->bindValue(':free_access', 1, PDO::PARAM_INT);
    }
    else {
        $sth->bindValue(':free_access', 0, PDO::PARAM_INT);
    }

    if (!empty($_POST['not_excluding']) && $admin_permissions) {
        $sth->bindValue(':not_excluding', 1, PDO::PARAM_INT);
    }
    else {
        $sth->bindValue(':not_excluding', 0, PDO::PARAM_INT);
    }

    if (!empty($_POST['switchid'])) {
        $switch = $_POST['switchid'];
        $sth->bindValue(':switchid', $switch, PDO::PARAM_INT);
    }
    else {
        $sth->bindValue(':switchid', 0, PDO::PARAM_INT);
    }

    if (!empty($_POST['use_pppoe'])) {
        $sth->bindValue(':pppoe', 1, PDO::PARAM_INT);
    }
    else {
        $sth->bindValue(':pppoe', 0, PDO::PARAM_INT);
    }               

    $sth->execute();

    // Return info for new user
    $sql = 'SELECT users.userid, traffic.price, traffic.local_in, traffic.local_out FROM users 
            LEFT JOIN traffic ON users.trafficid = traffic.trafficid 
            WHERE users.name = :name ORDER BY users.userid DESC LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':name', $name, PDO::PARAM_STR);
    $sth->execute();
    $user_info = $sth->fetch(PDO::FETCH_ASSOC);

    // Please do not change, prevent: "Notice: Array to string conversion in"
    if (is_array($user_info)) {
        $userid = $user_info['userid'];
        $donw_speed = $user_info['local_in'];
        $up_speed = $user_info['local_out'];
    }

    if (!empty($_POST['ip'])) {
        $ip = $_POST['ip'];
        
        // Reserve IP Address
        $sql = 'UPDATE `static_ippool` SET userid = :userid, trafficid = :trafficid, vlan = :vlan, mac = :mac, free_mac = :free_mac, name = :name WHERE ipaddress = :ipaddress';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
        $sth->bindValue(':trafficid', $tariff_plan, PDO::PARAM_INT);
        
        if (!empty($_POST['vlan'])) {
            $vlan = $_POST['vlan'];
            $sth->bindValue(':vlan', $vlan, PDO::PARAM_STR);
        }
        else {
            $vlan = NULL;
            $sth->bindValue(':vlan', '');
        }

        if (!empty($_POST['mac']) && IsValidMAC($_POST['mac'])) {
            $mac = $_POST['mac'];
            $sth->bindValue(':mac', $mac, PDO::PARAM_STR);
        }
        else {
            $mac = NULL;
            $sth->bindValue(':mac', '');
        }

        if (!empty($_POST['free_mac'])) {
            $free_mac = 1;
            $sth->bindValue(':free_mac', 1, PDO::PARAM_INT);
        }
        else {
            $free_mac = 0;
            $sth->bindValue(':free_mac', 0);
        }

        $sth->bindValue(':name', $name, PDO::PARAM_STR);
        $sth->bindValue(':ipaddress', $ip, PDO::PARAM_STR);
        $sth->execute();

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import admin_tools
admin_tools.add_ip_rules(ip = '$ip', vlan = '$vlan', mac = '$mac', free_mac = '$free_mac', donw_speed = '$donw_speed', up_speed = '$up_speed')
END";
        shell_exec($command);

        if (empty($_POST['free_access'])) {

            //Add payment for
            if (!empty($_POST['pppoe']['username'])) {
                $str = strip_tags($_POST['pppoe']['username']);
                $username = preg_replace('/\s+/', '_', $str);
            }
            
            $operator1 = 'system';
            $date_payment1 = date('Y-m-d H:i:s');
            $expires = date("Y-m-d", strtotime("$LIMITED_INTERNET_ACCESS days"))." 23:59";
            $sum = ($pay != 0.00) ? $pay : $user_info['price'];
            $notes = 'This payment is added automatically by the system for new users.';

            $sql = 'INSERT INTO `payments` (`userid`, `name`, `username`, `limited`, `operator1`, `date_payment1`, `expires`, `sum`, `notes`) 
                    VALUES (:userid, :name, :username, :limited, :operator1, :date_payment1, :expires, :sum, :notes)';
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':username', $username, PDO::PARAM_STR);
            $sth->bindValue(':limited', 1, PDO::PARAM_INT);
            $sth->bindValue(':operator1', $operator1, PDO::PARAM_STR);
            $sth->bindValue(':date_payment1', $date_payment1, PDO::PARAM_STR);
            $sth->bindValue(':expires', $expires, PDO::PARAM_INT);
            $sth->bindValue(':sum', $sum, PDO::PARAM_STR);
            $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
            $sth->execute();
        }
    } 
    
    // Insert PPPoE account
    if (!empty($_POST['use_pppoe']) && (!empty($_POST['pppoe']['username']) && !empty($_POST['pppoe']['password']))) {

        $str = strip_tags($_POST['pppoe']['username']);
        $username = preg_replace('/\s+/', '_', $str);
        $password = strip_tags($_POST['pppoe']['password']);
        $groupname = $_POST['groupname'];

        $sql = 'INSERT INTO `radcheck` ( `userid`, `username`, `attribute`, `op`, `value`)
                VALUES (:userid, :username, :attribute, :op, :value)';
        $db->dbh->beginTransaction();
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid);
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
        $sth->bindValue(':userid', $userid);
        $sth->bindValue(':username', $username, PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Expiration');
        $sth->bindValue(':op', ':=');
        $sth->execute();
        
        $sql = 'INSERT INTO `radcheck` (`userid`, `username`, `attribute`, `op`)
                VALUES (:userid, :username, :attribute, :op)';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid);
        $sth->bindValue(':username', $username, PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Calling-Station-Id');
        $sth->bindValue(':op', ':=');
        $sth->execute();

        $sql = 'INSERT INTO `radusergroup` (`userid`, `username`, `groupname`)
                VALUES (:userid, :username, :groupname)';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid);
        $sth->bindValue(':username', $username, PDO::PARAM_STR);
        $sth->bindValue(':groupname', $groupname, PDO::PARAM_STR);
        $sth->execute();

        $db->dbh->commit();
    }   

    $_SESSION['msg'] .=  _s('The new user %s is added successfully.', $name)."<br>";

    header("Location: user_edit.php?userid=$userid");
    exit;
}

?>
