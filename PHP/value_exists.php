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

if (!empty($_GET['table']) && !empty($_GET['value'])) {
	
	//enable debug mode
	//error_reporting(E_ALL); ini_set('display_errors', 'On');

	require_once dirname(__FILE__).'/include/common.php';

    // Check for active session
    if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

        header('Location: index.php');
        exit;
    }

	$db = new PDOinstance();

	$table = $_GET['table'];
	$value = $_GET['value'];
    $valueid = $_GET['valueid'];

	if ($table == 'ip_username') {

		$sql = 'SELECT username FROM ip WHERE id != ? AND username = ? LIMIT 1';
	}

	if ($table == 'ip_ip') {

		$sql = 'SELECT ip FROM ip WHERE id != ? AND userid != 0 AND ip = ? LIMIT 1';
	}

	if ($table == 'radgroupcheck') {

		$sql = 'SELECT groupname FROM radgroupcheck WHERE id != ? AND groupname = ? GROUP BY groupname LIMIT 1';
	}

    if ($table == 'operators') {

        $sql = 'SELECT alias FROM operators WHERE operid != ? AND alias = ? LIMIT 1';
    }

	$sth = $db->dbh->prepare($sql);
	$sth->bindParam(1, $valueid, PDO::PARAM_INT);
    $sth->bindParam(2, $value, PDO::PARAM_STR);
	$sth->execute();

	if ($sth->rowCount() == 1) {

		echo 0;
	}
	else {

		echo 1;
	}
}
else {
	header('Location: index.php');
}
?>
