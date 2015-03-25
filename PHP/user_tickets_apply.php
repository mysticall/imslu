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

###################################################################################################
    // Save new request
###################################################################################################

if (!empty($_POST['new'])) {

    $userid = $_POST['userid'];
    $operid = $_POST['operid'];
    $add = date('Y-m-d H:i:s');
    $created = date('Y-m-d H:i:s').' '.$_SESSION['data']['name'];
    $name = strip_tags($_POST['name']);
    $address = strip_tags($_POST['address']);
    $phone_number = strip_tags($_POST['phone_number']);
    $notes = $_POST['notes'];

    $sql = 'INSERT INTO `tickets` (`userid`, `operid`, `status`, `add`, `assign`, `end`, `created`, `notes`)
            VALUES (:userid, :operid, :status, :add, :assign, :end, :created, :notes)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->bindValue(':operid', $operid, PDO::PARAM_INT);
    $sth->bindValue(':status', '1', PDO::PARAM_INT);
    $sth->bindValue(':add', $add);
    $sth->bindValue(':assign', $_POST['assign']);
    $sth->bindValue(':end', $_POST['end']);
    $sth->bindValue(':created', $created);
    $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
    $sth->execute();
    
    header("Location: user_tickets.php?userid={$userid}");
    exit;
}

###################################################################################################
    // Edit ticket
###################################################################################################

if (!empty($_POST['edit'])) {

    $ticketid = $_POST['ticketid'];
    $operid = $_POST['operid'];
    $status = $_POST['status'];
    $changed = date('Y-m-d H:i:s').' '.$_SESSION['data']['name'];
    $name = strip_tags($_POST['name']);
    $address = strip_tags($_POST['address']);
    $phone_number = strip_tags($_POST['phone_number']);
    $notes = $_POST['notes'];

    $check_status = ($_POST['status'] == '0') ? '`closed`' : '`changed`';

    $sql = "UPDATE `tickets` SET `operid` = :operid, `status` = :status, `assign` = :assign, `end` = :end, $check_status = :changed, `notes` = :notes
            WHERE `ticketid` = :ticketid";
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':operid', $operid, PDO::PARAM_INT);
    $sth->bindValue(':status', $status, PDO::PARAM_INT);
    $sth->bindValue(':assign', $_POST['assign']);
    $sth->bindValue(':end', $_POST['end']);
    $sth->bindValue(':changed', $changed);
    $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
    $sth->bindValue(':ticketid', $_POST['ticketid'], PDO::PARAM_INT);
    $sth->execute();

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
    header("Location: user_tickets.php?userid={$_POST['userid']}");
}
?>
