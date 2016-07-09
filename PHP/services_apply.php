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

        $name = strip_tags($_POST['name']);

        $sql = 'DELETE FROM `services` WHERE name = :name';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':name', $name, PDO::PARAM_STR);
        $sth->execute();

        // Add audit
        add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_SYSTEM, "Service {$name} is deleted.", $_POST['old']);


        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: kind_traffic.php");
        exit;
    }

    ####### Edit #######
    if (!empty($_POST['edit'])) {

        $name = strip_tags($_POST['name']);

        $db->dbh->beginTransaction();
        $sql = 'UPDATE `services` SET name = :name, price = :price, in_min = :in_min, in_max = :in_max, out_min = :out_min, out_max = :out_max
                WHERE serviceid = :serviceid';

        foreach ($_POST['update'] as $value) {

            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':price', $_POST['price'], PDO::PARAM_INT);
            $sth->bindValue(':in_min', $value['in_min'], PDO::PARAM_STR);
            $sth->bindValue(':in_max', $value['in_max'], PDO::PARAM_STR);
            $sth->bindValue(':out_min', $value['out_min'], PDO::PARAM_STR);
            $sth->bindValue(':out_max', $value['out_max'], PDO::PARAM_STR);
            $sth->bindValue(':serviceid', $value['serviceid'], PDO::PARAM_INT);
            $sth->execute();
        }
        unset($value);

        $sql = 'INSERT INTO `services` (`kind_trafficid`, `name`, `price`, `in_min`, `in_max`, `out_min`, `out_max`) 
                VALUES (:kind_trafficid, :name, :price, :in_min, :in_max, :out_min, :out_max)';

        if (!empty($_POST['insert'])) {
            foreach ($_POST['insert'] as $value) {

                $sth = $db->dbh->prepare($sql);
                $sth->bindValue(':kind_trafficid', $value['kind_trafficid'], PDO::PARAM_INT);
                $sth->bindValue(':name', $name, PDO::PARAM_STR);
                $sth->bindValue(':price', $_POST['price'], PDO::PARAM_INT);
                $sth->bindValue(':in_min', $value['in_min'], PDO::PARAM_STR);
                $sth->bindValue(':in_max', $value['in_max'], PDO::PARAM_STR);
                $sth->bindValue(':out_min', $value['out_min'], PDO::PARAM_STR);
                $sth->bindValue(':out_max', $value['out_max'], PDO::PARAM_STR);
                $sth->execute();
            }
        }
        $db->dbh->commit();

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_SYSTEM, "Service {$name} is changed.", $_POST['old'], json_encode($_POST['update']));

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: services.php");
        exit;
    }

    ####### New ####### 
    if (!empty($_POST['new'])) {

        $sql = 'INSERT INTO `services` (`kind_trafficid`, `name`, `price`, `in_min`, `in_max`, `out_min`, `out_max`) 
                VALUES (:kind_trafficid, :name, :price, :in_min, :in_max, :out_min, :out_max)';
        $db->dbh->beginTransaction();

        for ($i = 0; $i < count($_POST['kind_traffic']); ++$i) {

            $name = strip_tags($_POST['name']);

            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':kind_trafficid', $_POST['kind_traffic'][$i]['kind_trafficid'], PDO::PARAM_INT);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':price', $_POST['price'], PDO::PARAM_INT);
            $sth->bindValue(':in_min', $_POST['kind_traffic'][$i]['in_min'], PDO::PARAM_STR);
            $sth->bindValue(':in_max', $_POST['kind_traffic'][$i]['in_max'], PDO::PARAM_STR);
            $sth->bindValue(':out_min', $_POST['kind_traffic'][$i]['out_min'], PDO::PARAM_STR);
            $sth->bindValue(':out_max', $_POST['kind_traffic'][$i]['out_max'], PDO::PARAM_STR);
            $sth->execute();
        }
        $db->dbh->commit();

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: services.php");
        exit;
    }

    unset($_POST);
    header("Location: services.php");
}
?>
