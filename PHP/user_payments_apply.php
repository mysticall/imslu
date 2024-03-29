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

// enable debug mode
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
$pay = 3;

if (!empty($_GET)) {

    # !!! Prevent problems !!!
    settype($_GET['userid'], "integer");
    settype($_GET['id'], "integer");
    if($_GET['userid'] == 0 || $_GET['id'] == 0) {
        header("Location: users.php");
        exit;
    }
    $userid = $_GET['userid'];

    if (!empty($_GET['pay_temporary'])) {

        $active_until = $_GET['active_until'];
        $pay = 1;

        if ($active_until > time()) {
            $expires = date("Y-m-d", strtotime("+$FEE_PERIOD -$TEMPORARY_INTERNET_ACCESS days", $active_until))." 23:59:00";
        }
        else {
            $expires = date("Y-m-d", strtotime("+$FEE_PERIOD -$TEMPORARY_INTERNET_ACCESS days"))." 23:59:00";
        }

        $sql = 'UPDATE payments SET limited = :limited, operator2 = :operator2, date_payment2 = :date_payment2, expires = :expires 
                WHERE id = :id AND userid = :userid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':limited', 0);
        $sth->bindValue(':operator2', $_SESSION['data']['alias']);
        $sth->bindValue(':date_payment2', date('Y-m-d H:i:s'));
        $sth->bindValue(':expires', $expires);
        $sth->bindValue(':id', $_GET['id']);
        $sth->bindValue(':userid', $userid);
        $sth->execute();
    }
    elseif (!empty($_GET['pay_unpaid'])) {

        $sql = 'UPDATE payments SET unpaid = :unpaid, operator2 = :operator2, date_payment2 = :date_payment2 
                WHERE id = :id AND userid = :userid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':unpaid', 0);
        $sth->bindValue(':operator2', $_SESSION['data']['alias']);
        $sth->bindValue(':date_payment2', date('Y-m-d H:i:s'));
        $sth->bindValue(':id', $_GET['id']);
        $sth->bindValue(':userid', $userid);
        $sth->execute();
    }
}

if (!empty($_POST)) {

    if ($_SESSION['form_key'] !== $_POST['form_key']) {

        header('Location: index.php');
        exit;
    }

    $old = json_decode($_POST['old'], true);
    $userid = $old['userid'];
    // last payment "expires" value
    $active_until = $_POST['active_until'];
    // current "expires" value
    $expires = !empty($_POST['limited_access']) ? $_POST['limited'] : $_POST['expires'];

    ####### Delete #######
    if (!empty($_POST['delete']) && !empty($_POST['del']) && $admin_permissions) {

        $old_p = json_decode($_POST['old_p'], true);
        $sql = 'DELETE FROM `payments` WHERE id = :id AND userid = :userid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':id', $old_p['id']);
        $sth->bindValue(':userid', $userid);
        $sth->execute();

        // Add audit
        add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_PAYMENTS, "Payment is deleted ID: {$old_p['id']}, Userid: {$old['userid']}, User: {$old['name']}.", "Payment info\n {$_POST['old_p']}");

        // The last payment has been deleted?
        if ($active_until == strtotime($old_p['expires'])) {
            $pay = 0;
            $_SESSION['msg'] .= _('Change user expiration date manually.')."<br>";
        }
    }

    ####### Update #######
    if (!empty($_POST['save']) && $admin_permissions) {

        $old_p = json_decode($_POST['old_p'], true);
        $sql = 'UPDATE payments SET expires = :expires, sum = :sum, notes = :notes
                WHERE id = :id AND userid = :userid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':expires', $_POST['expires']);
        $sth->bindValue(':sum', $_POST['sum']);
        $sth->bindValue(':notes', $_POST['notes']);
        $sth->bindValue(':id', $old_p['id']);
        $sth->bindValue(':userid', $userid);
        $sth->execute();

        if ($expires != $old_p['expires'] || $sum != $old_p['sum']) {

            $expire_old = strtotime($old_p['expires']);
            $expire_new = strtotime($_POST['expires']);

            // The last payment has been changed?
            if ($active_until == $expire_old && ($active_until > $expire_new && $expire_new < time())) {
                $pay = 0;
            }
            elseif ($active_until == $expire_old && ($expire_new > $active_until && $expire_new > time())) {
                $pay = 1;
            }

            // Add audit
            add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_PAYMENTS, "Pay is changed - ID: {$old_p['id']}, Userid: {$userid}, User: {$old['name']}.", "Expires - {$old_p['expires']} \nSum - {$old_p['sum']}", "Expires - {$_POST['expires']} \nSum - {$_POST['sum']}");        
        }
    }
    ####### New #######
    elseif (!empty($_POST['payment'])) {

        $pay = 1;
        $sql = 'INSERT INTO payments (userid, name, operator2, date_payment2, expires, sum, notes) 
                VALUES (:userid, :name, :operator2, :date_payment2, :expires, :sum, :notes)';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid);
        $sth->bindValue(':name', $old['name']);
        $sth->bindValue(':operator2', $_SESSION['data']['alias']);
        $sth->bindValue(':date_payment2', date('Y-m-d H:i:s'));
        $sth->bindValue(':expires', $_POST['expires']);
        $sth->bindValue(':sum', $_POST['sum']);
        $sth->bindValue(':notes', $_POST['notes']);
        $sth->execute();
    }
    elseif (!empty($_POST['obligation'])) {

        $pay = 1;
        $sql = 'INSERT INTO payments (userid, name, unpaid, operator1, date_payment1, expires, sum, notes) 
                VALUES (:userid, :name, :unpaid, :operator1, :date_payment1, :expires, :sum, :notes)';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $userid);
        $sth->bindValue(':name', $old['name']);
        $sth->bindValue(':unpaid', 1);
        $sth->bindValue(':operator1', $_SESSION['data']['alias']);
        $sth->bindValue(':date_payment1', date('Y-m-d H:i:s'));
        $sth->bindValue(':expires', $_POST['expires']);
        $sth->bindValue(':sum', $_POST['sum']);
        $sth->bindValue(':notes', $_POST['notes']);
        $sth->execute();
    }
}

if (isset($active_until) && isset($userid)) {

    $expires = empty($_POST['delete']) ? $expires : date("Y-m-d", strtotime("-1 day"))." 23:59:00";

    $sql = 'UPDATE users SET expires = :expires WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':expires', $expires);
    $sth->bindValue(':userid', $userid);
    $sth->execute();

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

  // User serviceid, required from FreeBSD shaper
  if (!empty($old['serviceid'])) {
    $serviceid = $old['serviceid'];
  } else {

    $sql = 'SELECT serviceid FROM users WHERE userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);
    $serviceid = $user['serviceid'];
  }

    // Select user IP Addresses
    $sql = 'SELECT ip FROM ip WHERE userid = :userid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $ip = $sth->fetchAll(PDO::FETCH_ASSOC);

    // Start internet access
    if (!empty($ip)) {

        // last payment is expired and new payment is great from now
        if ($pay == 1 && $active_until < time() && strtotime($expires) > time()) {
            for ($i = 0; $i < count($ip); ++$i) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow '{$ip[$i]['ip']}' '{$serviceid}'";
                shell_exec($cmd);
            }
        }
        elseif ($pay == 0) {
            for ($i = 0; $i < count($ip); ++$i) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_stop {$ip[$i]['ip']}";
                shell_exec($cmd);
            }
        }
    }
}

header("Location: user_payments.php?userid={$userid}");
?>
