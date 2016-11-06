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

// enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

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

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);

$old = json_decode($_POST['old'], true);
$ip = json_decode($_POST['ip'], true);
$userid = $old['userid'];

####### Delete ####### 
// Onli System Admin or Admin can delete User
if (!empty($_POST['delete']) && !empty($_POST['del_user']) && $admin_permissions) {

    $sql = 'DELETE FROM users WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();

    // Add audit
    add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_USER, "User: {$old['name']} is deleted.", "User\n {$_POST['old']} \nIP addresses:\n".json_encode($ip));

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

    if (!empty($ip)) {
        for ($i = 0; $i < count($ip); ++$i) {

            ip_remove($db, $ip[$i]);
        }
    }

    // Remove tc class for user
    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_class_delete {$userid} 2>&1";
    $result = shell_exec($cmd);
    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";

    unset($_POST);
    header("Location: users.php");
    exit;
}


####### Edit #######
if (!empty($_POST['edit'])) {

    $now = date ("YmdHis");
    $expire = date("YmdHis", strtotime("{$_POST['expires']}"));

    # Here there are too many checks, because table "users" is indexed.
    # It is not advisable, to make unnecessary entries in this table.
    $update = array();

    if ($old['name'] != $_POST['name']) {
        
        $update['name'] = strip_tags($_POST['name']);

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "First Name Surname is changed - ID: $userid.", "{$old['name']}", "{$update['name']}");
    }
    $_POST['locationid'] = !empty($_POST['locationid']) ? $_POST['locationid'] : 0;
    if ($old['locationid'] != $_POST['locationid']) {
        
        $update['locationid'] = $_POST['locationid'];
    }
    if ($old['address'] != $_POST['address']) {
        
        $update['address'] = strip_tags($_POST['address']);
    }
    if ($old['phone_number'] != $_POST['phone_number']) {
        
        $update['phone_number'] = strip_tags($_POST['phone_number']);
    }
    if ($old['notes'] != $_POST['notes']) {
        
        $update['notes'] = $_POST['notes'];
    }
    if (($old['serviceid'] != $_POST['serviceid']) && ($admin_permissions || $cashier_permissions)) {

        $update['serviceid'] = $_POST['serviceid'];
        // Replace tc class for user
        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_class_replace {$old['userid']} {$_POST['serviceid']} 2>&1";
        $result = shell_exec($cmd);
        $_SESSION['msg'] .= ($result) ? "$result <br>" : "";

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "The tariff plan is changed - ID: $userid, User: {$_POST['name']}.", "Service - {$old['serviceid']}", "Service - {$_POST['serviceid']}");
    }
    
    $_POST['pay'] = !empty($_POST['pay']) ? $_POST['pay'] : '0.00';
    if (($old['pay'] != $_POST['pay']) && ($admin_permissions)) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_PAYMENTS, "Pay is changed - ID: $userid, User: {$_POST['name']}.", "Pay - {$old['pay']}", "Pay - {$_POST['pay']}");        

        $update['pay'] = $_POST['pay'];
    }

    // Checks for free internet access
    if (($old['free_access'] != $_POST['free_access']) && $admin_permissions) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "Free Internet Access is changed - ID: $userid, User: {$_POST['name']}.", "{$old['free_access']}", "{$_POST['free_access']}");        
        
        $update['free_access'] = $_POST['free_access'];

        // Start internet access
        if ($_POST['free_access'] == 'y' && !empty($ip[0]['ip'])) {

            for ($i = 0; $i < count($ip); ++$i) {
        
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow {$ip[$i]['ip']} 2>&1";
                $result = shell_exec($cmd);
                $_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip[$i]['ip']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip[$i]['ip']}").' - '.$result.'<br>';
            }
        }
        // Stop internet access
        if ($_POST['free_access'] == 'n' && !empty($ip[0]['ip']) && (empty($_POST['expires']) || $expire < $now)) {

            for ($i = 0; $i < count($ip); ++$i) {
                
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_stop {$ip[$i]['ip']} 2>&1";
                $result = shell_exec($cmd);
                $_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is stopped.', "{$ip[$i]['ip']}").'<br>' : _s('Stopping internet access for IP address %s is failed', "{$ip[$i]['ip']}").' - '.$result.'<br>';
            }
        }        
    }

    if (($old['not_excluding'] != $_POST['not_excluding']) && $admin_permissions) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_USER, "Not excluding is changed - ID: $userid, User: {$_POST['name']}.", "{$old['not_excluding']}", "{$_POST['not_excluding']}");        

        $update['not_excluding'] = $_POST['not_excluding'];
    }

    # Check for changes on payments and update
    if (!empty($_POST['expires']) && ($old['expires'] != $_POST['expires']) && $admin_permissions) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_PAYMENTS, "Active until is changed - ID: $userid, User: {$_POST['name']}.", "{$old['expires']}", "{$_POST['expires']}");

        $update['expires'] = $_POST['expires'];

        // Start internet access
        if (($_POST['free_access'] == 'y' || $expire > $now) && !empty($ip[0]['ip'])) {

            for ($i = 0; $i < count($ip); ++$i) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow {$ip[$i]['ip']} 2>&1";
                $result = shell_exec($cmd);
                $_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip[$i]['ip']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip[$i]['ip']}").' - '.$result.'<br>';
            }
        }
        // Stop internet access
        if ($_POST['free_access'] == 'n' && $expire < $now && !empty($ip[0]['ip'])) {

            for ($i = 0; $i < count($ip); ++$i) {
                    
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_stop {$ip[$i]['ip']} 2>&1";
                $result = shell_exec($cmd);
                $_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is stopped.', "{$ip[$i]['ip']}").'<br>' : _s('Stopping internet access for IP address %s is failed', "{$ip[$i]['ip']}").' - '.$result.'<br>';
            }
        }
    }

    if (!empty($update)) {

        $i = 1;
        foreach($update as $key => $value) {
            $keys[$i] = $key;
               $values[$i] = $value;

        $i++;
        }

        $sql = 'UPDATE users SET '.implode(' = ?, ', $keys).' = ? WHERE userid = ?';

        array_push($values, $userid);
        $db->prepare_array($sql, $values);
    }

    header("Location: user.php?userid={$old['userid']}");
}
?>
