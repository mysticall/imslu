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

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);


###################################################################################################
	// Delet payment
###################################################################################################

if (!empty($_POST['delete']) && !empty($_POST['del']) && $admin_permissions && !empty($_POST['id']) && !empty($_POST['userid'])) {

	$id = $_POST['id'];
	$userid = $_POST['userid'];

	$sql = 'DELETE FROM `payments` WHERE id = :id AND userid = :userid';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':id', $id, PDO::PARAM_INT);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();

	// Add audit
	add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_PAYMENTS, "Payment is deleted ID: $id, Userid: $userid, User: {$_POST['name']}.", "Payment info\n".json_encode($_SESSION['payment_info']));

	header("Location: user_payments.php?userid=$userid");
}

###################################################################################################
	// Update payment
###################################################################################################

if (!empty($_POST['save']) && !empty($_POST['id']) && !empty($_POST['userid'])) {
	
	$id = $_POST['id'];
	$userid = $_POST['userid'];
	$expires = $_POST['expires'];
	$sum = $_POST['sum'];
	$notes = $_POST['notes'];

	$sql = 'UPDATE `payments` SET expires = :expires, sum = :sum, notes = :notes
			WHERE id = :id AND userid = :userid';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':expires', $expires, PDO::PARAM_INT);
	$sth->bindValue(':sum', $sum, PDO::PARAM_INT);
	$sth->bindValue(':notes', $notes, PDO::PARAM_STR);
	$sth->bindValue(':id', $id, PDO::PARAM_INT);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();

	if ($expires != $_SESSION['payment_info']['expires'] || $sum != $_SESSION['payment_info']['sum']) {
		
		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_PAYMENTS, "Pay is changed - ID: $userid, Userid: $userid, User: {$_POST['name']}.", "Expires - {$_SESSION['payment_info']['expires']} \nSum - {$_SESSION['payment_info']['sum']}", "Expires - $expires \nSum - $sum");		
	}

	header("Location: user_payments.php?userid=$userid");
}

###################################################################################################
	//Save new payment
###################################################################################################

if (!empty($_POST['payment']) && !empty($_POST['userid'])) {

	$userid = $_POST['userid'];
	$name = $_POST['name'];
	$username = $_POST['username'];
	$operator2 = $_SESSION['data']['alias'];
	$date_payment2 = date('Y-m-d H:i:s');
	$expires = $_POST['expires'];
	$sum = $_POST['sum'];
	$notes = $_POST['notes'];
	
	$sql = 'INSERT INTO `payments` (`userid`, `name`, `username`, `operator2`, `date_payment2`, `expires`, `sum`, `notes`) 
			VALUES (:userid, :name, :username, :operator2, :date_payment2, :expires, :sum, :notes)';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->bindValue(':name', $name, PDO::PARAM_STR);
	$sth->bindValue(':username', $username, PDO::PARAM_STR);
	$sth->bindValue(':operator2', $operator2, PDO::PARAM_STR);
	$sth->bindValue(':date_payment2', $date_payment2);
	$sth->bindValue(':expires', $expires, PDO::PARAM_INT);
	$sth->bindValue(':sum', $sum, PDO::PARAM_INT);
	$sth->bindValue(':notes', $notes, PDO::PARAM_STR);
	$sth->execute();

	if (!empty($_POST['start_internet'])) {

		$sql = 'SELECT ipaddress
				FROM static_ippool
				WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();
		$ip_addresses = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($ip_addresses[0]['ipaddress'])) {

			// Start internet access
			for ($i = 0; $i < count($ip_addresses); ++$i) {
		
				$result = shell_exec("$SUDO $IP rule del from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
			}
		}
	}
	
	header("Location: user_payments.php?userid=$userid");
}

if (!empty($_POST['obligation']) && !empty($_POST['userid'])) {

	$userid = $_POST['userid'];
	$name = $_POST['name'];
	$username = $_POST['username'];
	$operator1 = $_SESSION['data']['alias'];
	$date_payment1 = date('Y-m-d H:i:s');
	$expires = $_POST['expires'];
	$sum = $_POST['sum'];
	$notes = $_POST['notes'];
	
	$sql = 'INSERT INTO `payments` (`userid`, `name`, `username`, `unpaid`, `operator1`, `date_payment1`, `expires`, `sum`, `notes`) 
			VALUES (:userid, :name, :username, :unpaid, :operator1, :date_payment1, :expires, :sum, :notes)';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->bindValue(':name', $name, PDO::PARAM_STR);
	$sth->bindValue(':username', $username, PDO::PARAM_STR);
	$sth->bindValue(':unpaid', 1, PDO::PARAM_INT);
	$sth->bindValue(':operator1', $operator1, PDO::PARAM_STR);
	$sth->bindValue(':date_payment1', $date_payment1);
	$sth->bindValue(':expires', $expires, PDO::PARAM_INT);
	$sth->bindValue(':sum', $sum, PDO::PARAM_INT);
	$sth->bindValue(':notes', $notes, PDO::PARAM_STR);
	$sth->execute();

	if (!empty($_POST['start_internet'])) {

		$sql = 'SELECT ipaddress
				FROM static_ippool
				WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();
		$ip_addresses = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($ip_addresses[0]['ipaddress'])) {

			// Start internet access
			for ($i = 0; $i < count($ip_addresses); ++$i) {
		
				$result = shell_exec("$SUDO $IP rule del from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
			}
		}
	}

	header("Location: user_payments.php?userid=$userid");
}

if (!empty($_POST['limited_access']) && !empty($_POST['userid'])) {

	$userid = $_POST['userid'];
	$name = $_POST['name'];
	$username = $_POST['username'];
	$operator1 = $_SESSION['data']['alias'];
	$date_payment1 = date('Y-m-d H:i:s');
	$expires = $_POST['limited'];
	$sum = $_POST['sum'];
	$notes = $_POST['notes'];
	
	$sql = 'INSERT INTO `payments` (`userid`, `name`, `username`, `limited`, `operator1`, `date_payment1`, `expires`, `sum`, `notes`) 
			VALUES (:userid, :name, :username, :limited, :operator1, :date_payment1, :expires, :sum, :notes)';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->bindValue(':name', $name, PDO::PARAM_STR);
	$sth->bindValue(':username', $username, PDO::PARAM_STR);
	$sth->bindValue(':limited', 1, PDO::PARAM_INT);
	$sth->bindValue(':operator1', $operator1, PDO::PARAM_STR);
	$sth->bindValue(':date_payment1', $date_payment1);
	$sth->bindValue(':expires', $expires, PDO::PARAM_INT);
	$sth->bindValue(':sum', $sum, PDO::PARAM_STR);
	$sth->bindValue(':notes', $notes, PDO::PARAM_STR);
	$sth->execute();

	if (!empty($_POST['start_internet'])) {

		$sql = 'SELECT ipaddress
				FROM static_ippool
				WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();
		$ip_addresses = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($ip_addresses[0]['ipaddress'])) {

			// Start internet access
			for ($i = 0; $i < count($ip_addresses); ++$i) {
		
				$result = shell_exec("$SUDO $IP rule del from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
			}
		}
	}

	header("Location: user_payments.php?userid=$userid");
}

if (!empty($_POST['pay_limited']) && !empty($_POST['userid'])) {

	$id = $_POST['pay_limited'];
	$userid = $_POST['userid'];
	$operator2 = $_SESSION['data']['alias'];
	$date_payment2 = date('Y-m-d H:i:s');

	$expires_limited = $_POST["expires_$id"];

	if ($expires_limited > date('Y-m-d H:i')) {
		
		$time = strtotime("$expires_limited");
		$expires = date("Y-m-d", strtotime("+1 month -$LIMITED_INTERNET_ACCESS days", $time))." 23:59";
	}
	else {
		$expires = date("Y-m-d", strtotime("+1 month -$LIMITED_INTERNET_ACCESS days"))." 23:59";
	}

	$sql = 'UPDATE `payments` SET limited = :limited, operator2 = :operator2, date_payment2 = :date_payment2, expires = :expires 
			WHERE id = :id AND userid = :userid';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':limited', 0, PDO::PARAM_INT);
	$sth->bindValue(':operator2', $operator2, PDO::PARAM_STR);
	$sth->bindValue(':date_payment2', $date_payment2);
	$sth->bindValue(':expires', $expires, PDO::PARAM_INT);
	$sth->bindValue(':id', $id, PDO::PARAM_INT);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();

	if (!empty($_POST['start_internet'])) {

		$sql = 'SELECT ipaddress
				FROM static_ippool
				WHERE userid = :userid';
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':userid', $userid, PDO::PARAM_INT);
		$sth->execute();
		$ip_addresses = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($ip_addresses[0]['ipaddress'])) {

			// Start internet access
			for ($i = 0; $i < count($ip_addresses); ++$i) {
		
				$result = shell_exec("$SUDO $IP rule del from {$ip_addresses[$i]['ipaddress']} table EXPIRED 2>&1");
				$_SESSION['msg'] .= (empty($result)) ? _s('Internet access for IP address %s is enabled.', "{$ip_addresses[$i]['ipaddress']}").'<br>' : _s('Enabling internet access for IP address %s is failed', "{$ip_addresses[$i]['ipaddress']}").' - '.$result.'<br>';
			}
		}
	}

	header("Location: user_payments.php?userid=$userid");
}

if (!empty($_POST['pay_unpaid']) && !empty($_POST['userid'])) {

	$id = $_POST['pay_unpaid'];
	$userid = $_POST['userid'];
	$operator2 = $_SESSION['data']['alias'];
	$date_payment2 = date('Y-m-d H:i:s');

	$sql = 'UPDATE `payments` SET unpaid = :unpaid, operator2 = :operator2, date_payment2 = :date_payment2 
			WHERE id = :id AND userid = :userid';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':unpaid', 0, PDO::PARAM_INT);
	$sth->bindValue(':operator2', $operator2, PDO::PARAM_STR);
	$sth->bindValue(':date_payment2', $date_payment2);
	$sth->bindValue(':id', $id, PDO::PARAM_INT);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();

	header("Location: user_payments.php?userid=$userid");
}

header("Location: user_payments.php?userid={$_POST['userid']}");
?>
