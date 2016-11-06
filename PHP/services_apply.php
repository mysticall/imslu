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

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();

    ####### Delete #######
    if(!empty($_POST['del']) && !empty($_POST['delete'])) {

        $old = json_decode($_POST['old'], true);

        $sql = 'DELETE FROM `services` WHERE serviceid = :serviceid';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':serviceid', $old['serviceid'], PDO::PARAM_STR);
        $sth->execute();

        // Add audit
        add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_SYSTEM, "Service {$_POST['name']} is deleted.", $_POST['old']);


        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: services.php");
        exit;
    }

    ####### Edit #######
    if (!empty($_POST['edit'])) {

        $old = json_decode($_POST['old'], true);

        $i = 1;
        foreach($_POST['update'] as $key => $value) {
            $keys[$i] = $key;
               $values[$i] = $value;

        $i++;
        }

        $sql = 'UPDATE services SET '.implode(' = ?, ', $keys).' = ? WHERE serviceid = ?';

        array_push($values, $old['serviceid']);
        $db->prepare_array($sql, $values);

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_SYSTEM, "Service {$_POST['old']['name']} is changed.", $_POST['old'], json_encode($_POST['update']));

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: services.php");
        exit;
    }

    ####### New ####### 
    if (!empty($_POST['new'])) {

        $i = 1;
        foreach($_POST['insert'] as $key => $value) {
            $keys[$i] = $key;
               $values[$i] = $value;

        $i++;
        }

        $sql = 'INSERT INTO services ('.implode(', ', $keys).') VALUES (:'.implode(', :', $keys).')';

        $db->prepare_array($sql, $values);

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: services.php");
        exit;
    }

    unset($_POST);
    header("Location: services.php");
}
?>
