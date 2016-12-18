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

if ($_SESSION['form_key'] !== $_POST['form_key']) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

####### New #######
if (!empty($_POST['new'])) {

    $name = strip_tags($_POST['name']);

    $sql = 'INSERT INTO users (name, locationid, address, phone_number, notes, created, serviceid, pay, free_access, not_excluding)
            VALUES (:name, :locationid, :address, :phone_number, :notes, :created, :serviceid, :pay, :free_access, :not_excluding)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':name', $name, PDO::PARAM_STR);
    $sth->bindValue(':locationid', $_POST['locationid'], PDO::PARAM_INT);
    $sth->bindValue(':address', strip_tags($_POST['address']), PDO::PARAM_STR);
    $sth->bindValue(':phone_number', strip_tags($_POST['phone_number']), PDO::PARAM_INT);
    $sth->bindValue(':notes', $_POST['notes'], PDO::PARAM_STR);
    $sth->bindValue(':created', date('Y-m-d H:i:s'));
    $sth->bindValue(':serviceid', $_POST['serviceid'], PDO::PARAM_INT);

    if ($admin_permissions) {
        $sth->bindValue(':pay', $_POST['pay'], PDO::PARAM_INT);
        $sth->bindValue(':free_access', $_POST['free_access'], PDO::PARAM_STR);
        $sth->bindValue(':not_excluding', $_POST['not_excluding'], PDO::PARAM_STR);
    }
    else {
        $sth->bindValue(':pay', '0.00');
        $sth->bindValue(':free_access', 'n', PDO::PARAM_STR);
        $sth->bindValue(':not_excluding', 'n', PDO::PARAM_STR);
    }
    $sth->execute();

    // Return info for new user
    $sql = 'SELECT userid FROM users WHERE name = :name ORDER BY userid DESC LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':name', $name, PDO::PARAM_STR);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);

    // Add tc class for user
    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_class_add {$user['userid']} {$_POST['serviceid']} 2>&1";
    $result = shell_exec($cmd);

    header("Location: user.php?userid={$user['userid']}");
    exit;
}
?>