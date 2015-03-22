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

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();


###################################################################################################
	// Update the location
###################################################################################################

if (!empty($_POST['save_edited']) && !empty($_POST['trafficid'])) {

	$id = $_POST['trafficid'];

	if(empty($_POST['name'])) {

		$msg['msg_name'] = _('Name cannot empty.');
		show_error_message('trafficid', $id, null, $msg, 'traffic_control.php');
	exit;
	}
	if(empty($_POST['price'])) {

		$msg['msg_price'] = _('Price cannot empty.');
		show_error_message('trafficid', $id, null, $msg, 'traffic_control.php');
	exit;
	}
	if(empty($_POST['local_in'])) {

		$msg['msg_local_in'] = _('Local in cannot empty.');
		show_error_message('trafficid', $id, null, $msg, 'traffic_control.php');
	exit;
	}	
	if(empty($_POST['local_out'])) {

		$msg['msg_local_out'] = _('Local out cannot empty.');
		show_error_message('trafficid', $id, null, $msg, 'traffic_control.php');
	exit;
	}			
/*	if(empty($_POST['int_in'])) {

		$msg['msg_int_in'] = _('international in cannot empty.');
		show_error_message('trafficid', $id, null, $msg, 'traffic_control.php');
	exit;
	}
	if(empty($_POST['int_out'])) {

		$msg['msg_int_out'] = _('international out cannot empty.');
		show_error_message('trafficid', $id, null, $msg, 'traffic_control.php');
	exit;
	}
*/
	$name = strip_tags($_POST['name']);
	$price = $_POST['price'];
	$local_in = $_POST['local_in'];
	$local_out = $_POST['local_out'];
/*	$int_in = $_POST['int_in'];
	$int_out = $_POST['int_out'];

	$sql = 'UPDATE `traffic` SET name = :name, price = :price, local_in = :local_in, local_out = :local_out, int_in = :int_in, int_out = :int_out
			WHERE trafficid = :trafficid';

 */
	$sql = 'UPDATE `traffic` SET name = :name, price = :price, local_in = :local_in, local_out = :local_out
			WHERE trafficid = :trafficid';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':name', $name, PDO::PARAM_STR);
	$sth->bindValue(':price', $price, PDO::PARAM_INT);
	$sth->bindValue(':local_in', $local_in, PDO::PARAM_INT);
	$sth->bindValue(':local_out', $local_out, PDO::PARAM_INT);
//	$sth->bindValue(':int_in', $int_in, PDO::PARAM_INT);
//	$sth->bindValue(':int_out', $int_out, PDO::PARAM_INT);
	$sth->bindValue(':trafficid', $id, PDO::PARAM_INT);
	$sth->execute();

	$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
	unset($_POST);
}

###################################################################################################
	//Save new location
###################################################################################################

if (!empty($_POST['save_new'])) {

	if(empty($_POST['name'])) {

		$msg['msg_name'] = _('Name cannot empty.');
		show_error_message('action', 'new_tariff_plan', null, $msg, 'traffic_control.php');
	exit;
	}
	if(empty($_POST['price'])) {

		$msg['msg_price'] = _('Price cannot empty.');
		show_error_message('action', 'new_tariff_plan', null, $msg, 'traffic_control.php');
	exit;
	}
	if(empty($_POST['local_in'])) {

		$msg['msg_local_in'] = _('Local in cannot empty.');
		show_error_message('action', 'new_tariff_plan', null, $msg, 'traffic_control.php');
	exit;
	}	
	if(empty($_POST['local_out'])) {

		$msg['msg_local_out'] = _('Local out cannot empty.');
		show_error_message('action', 'new_tariff_plan', null, $msg, 'traffic_control.php');
	exit;
	}			
/*	if(empty($_POST['int_in'])) {

		$msg['msg_int_in'] = _('International in cannot empty.');
		show_error_message('action', 'new_tariff_plan', null, $msg, 'traffic_control.php');
	exit;
	}
	if(empty($_POST['int_out'])) {

		$msg['msg_int_out'] = _('International out cannot empty.');
		show_error_message('action', 'new_tariff_plan', null, $msg, 'traffic_control.php');
	exit;
	}
*/
	$name = strip_tags($_POST['name']);
	$price = $_POST['price'];
	$local_in = $_POST['local_in'];
	$local_out = $_POST['local_out'];
/*	$int_in = $_POST['int_in'];
	$int_out = $_POST['int_out'];

	$sql = 'INSERT INTO `traffic` (`name`, `price`, `local_in`, `local_out`, `int_in`, `int_out`) 
			VALUES (:name, :price, :local_in, :local_out, :int_in, :int_out)';
*/
	$sql = 'INSERT INTO `traffic` (`name`, `price`, `local_in`, `local_out`) 
			VALUES (:name, :price, :local_in, :local_out)';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':name', $name, PDO::PARAM_STR);
	$sth->bindValue(':price', $price, PDO::PARAM_INT);
	$sth->bindValue(':local_in', $local_in, PDO::PARAM_INT);
	$sth->bindValue(':local_out', $local_out, PDO::PARAM_INT);
//	$sth->bindValue(':int_in', $int_in, PDO::PARAM_INT);
//	$sth->bindValue(':int_out', $int_out, PDO::PARAM_INT);
	$sth->execute();

	unset($_POST);
}

	header("Location: traffic_control.php");
}
?>