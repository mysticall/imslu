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

if (!empty($_POST['table']) && !empty($_POST['value'])) {
	
	//enable debug mode
	//error_reporting(E_ALL); ini_set('display_errors', 'On');

	require_once dirname(__FILE__).'/include/common.inc.php';

	if (!CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {
		header('Location: index.php');
		exit;
	}
	
	$db = new CPDOinstance();

	$table = $_POST['table'];
	$value = $_POST['value'];

	if ($table == 'radcheck') {
		
		$sql = 'SELECT username FROM radcheck WHERE username = ? GROUP BY username LIMIT 1';
	}
	
	if ($table == 'radgroupcheck') {
		
		$sql = 'SELECT groupname FROM radgroupcheck WHERE groupname = ? GROUP BY groupname LIMIT 1';
	}

	$sth = $db->dbh->prepare($sql);
	$sth->bindParam(1, $value, PDO::PARAM_STR);
	$sth->execute();

	if ($sth->rowCount() == 1) {

		echo "taken";
	}
	else {

		echo "free";
	}
}
else {
	header('Location: index.php');
}
?>
