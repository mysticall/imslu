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

//enable debug mode
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

####### New #######
if (!empty($_POST['new'])) {

    $operid = $_POST['operid'];
    $add = date('Y-m-d H:i:s');
    $status = $_POST['status'];
    $created = date('Y-m-d H:i:s').' '.$_SESSION['data']['name'];
    $name = strip_tags($_POST['name']);
    $address = strip_tags($_POST['address']);
    $phone_number = strip_tags($_POST['phone_number']);
    $notes = $_POST['notes'];

    $sql = 'INSERT INTO `requests` (`operid`, `status`, `add`, `assign`, `end`, `created`, `name`, `address`, `phone_number`, `notes`)
            VALUES (:operid, :status, :add, :assign, :end, :created, :name, :address, :phone_number, :notes)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':operid', $operid, PDO::PARAM_INT);
    $sth->bindValue(':status', $status, PDO::PARAM_INT);
    $sth->bindValue(':add', $add);
    $sth->bindValue(':assign', $_POST['assign']);
    $sth->bindValue(':end', $_POST['end']);
    $sth->bindValue(':created', $created);
    $sth->bindValue(':name', $name, PDO::PARAM_STR);
    $sth->bindValue(':address', $address, PDO::PARAM_STR);
    $sth->bindValue(':phone_number', $phone_number, PDO::PARAM_INT);
    $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
    $sth->execute();
    
    header("Location: requests.php");
    exit;
}


####### Edit #######
if (!empty($_POST['edit'])) {

    // see $request_status in /include/common.php
    if (($_POST['status']) == '3') {

        $operid = $_POST['operid'];
        $status = $_POST['status'];
        $changed = date('Y-m-d H:i:s').' '.$_SESSION['data']['name'];
        $name = strip_tags($_POST['name']);
        $address = strip_tags($_POST['address']);
        $phone_number = strip_tags($_POST['phone_number']);
        $notes = $_POST['notes'];

        $sql = 'UPDATE `requests` SET `operid` = :operid, `status` = :status, `assign` = :assign, `end` = :end, `changed` = :changed, `name` = :name,
                       `address` = :address, `phone_number` = :phone_number, `notes` = :notes
                WHERE `requestid` = :requestid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':operid', $operid, PDO::PARAM_INT);
        $sth->bindValue(':status', $status, PDO::PARAM_INT);
        $sth->bindValue(':assign', $_POST['assign']);
        $sth->bindValue(':end', $_POST['end']);
        $sth->bindValue(':changed', $changed);
        $sth->bindValue(':name', $name, PDO::PARAM_STR);
        $sth->bindValue(':address', $address, PDO::PARAM_STR);
        $sth->bindValue(':phone_number', $phone_number, PDO::PARAM_INT);
        $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
        $sth->bindValue(':requestid', $_POST['requestid'], PDO::PARAM_INT);
        $sth->execute();

        header("Location: user_new.php?name={$name}&address={$address}&phone_number={$phone_number}&notes={$notes}");
        exit;
    }

    $operid = $_POST['operid'];
    $status = $_POST['status'];
    $changed = date('Y-m-d H:i:s').' '.$_SESSION['data']['name'];
    $name = strip_tags($_POST['name']);
    $address = strip_tags($_POST['address']);
    $phone_number = strip_tags($_POST['phone_number']);
    $notes = $_POST['notes'];

    $check_status = ($_POST['status'] == '5') ? '`closed`' : '`changed`';

    $sql = "UPDATE `requests` SET `operid` = :operid, `status` = :status, `assign` = :assign, `end` = :end, $check_status = :changed, `name` = :name,
                   `address` = :address, `phone_number` = :phone_number, `notes` = :notes
            WHERE `requestid` = :requestid";
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':operid', $operid, PDO::PARAM_INT);
    $sth->bindValue(':status', $status, PDO::PARAM_INT);
    $sth->bindValue(':assign', $_POST['assign']);
    $sth->bindValue(':end', $_POST['end']);
    $sth->bindValue(':changed', $changed);
    $sth->bindValue(':name', $name, PDO::PARAM_STR);
    $sth->bindValue(':address', $address, PDO::PARAM_STR);
    $sth->bindValue(':phone_number', $phone_number, PDO::PARAM_INT);
    $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
    $sth->bindValue(':requestid', $_POST['requestid'], PDO::PARAM_INT);
    $sth->execute();

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
    header("Location: requests.php");
}
?>
