<?php
// enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');
require_once '/usr/share/imslu/include/common.php';
require_once '/usr/share/imslu/include/config.php';

$db = new PDOinstance();

$sql = "SELECT id, mac FROM ip WHERE userid != '0' AND mac NOT LIKE ''";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if (!empty($rows)) {

	$sql = "UPDATE ip SET mac = :mac WHERE id = :id";
	$db->dbh->beginTransaction();
	foreach ($rows as $value) {

		$mac = strtolower($value['mac']);
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':mac', $mac, PDO::PARAM_STR);
		$sth->bindParam(':id', $value['id'], PDO::PARAM_INT);
		$sth->execute();
	}
	$db->dbh->commit();
}
unset($rows);


$sql = "SELECT id, value FROM radcheck WHERE attribute = 'Calling-Station-Id'";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if (!empty($rows)) {

	$sql = "UPDATE radcheck SET value = :mac WHERE id = :id AND attribute = 'Calling-Station-Id'";
	$db->dbh->beginTransaction();
	foreach ($rows as $value) {

		$mac = strtolower($value['value']);
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':mac', $mac, PDO::PARAM_STR);
		$sth->bindParam(':id', $value['id'], PDO::PARAM_INT);
		$sth->execute();
	}
	$db->dbh->commit();
}
unset($rows);

?>
