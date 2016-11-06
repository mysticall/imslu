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
    if(!empty($_POST['delete'])) {

        for ($i = 1; $i < count($_POST['kind_traffic']); ++$i) {

            if(!empty($_POST['kind_traffic'][$i]['del'])) {

                $sql = 'DELETE FROM `kind_traffic` WHERE id = :id';
                $sth = $db->dbh->prepare($sql);
                $sth->bindValue(':id', $_POST['kind_traffic'][$i]['id'], PDO::PARAM_INT);
                $sth->execute();

                $sql = "UPDATE `services` SET in_min{$i} = :in_min{$i}, in_max{$i} = :in_max{$i}, out_min{$i} = :out_min{$i}, out_max{$i} = :out_max{$i}";
                $sth = $db->dbh->prepare($sql);
                $sth->bindValue("in_min{$i}", '32kbit');
                $sth->bindValue("in_max{$i}", 'NULL');
                $sth->bindValue("out_min{$i}", '32kbit');
                $sth->bindValue("out_max{$i}", 'NULL');
                $sth->execute();

                // Add audit
                add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_SYSTEM, "{$_POST['kind_traffic'][$i]['name']} traffic is deleted.", "id - {$_POST['kind_traffic'][$i]['id']}; name - {$_POST['kind_traffic'][$i]['name']}");
            }
        }

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: kind_traffic.php");
        exit;
    }

    ####### Edit ####### 
    if(!empty($_POST['edit'])) {

        // set all old values for comparison
        $old = json_decode($_POST['old'], true);
        for ($i = 0; $i < count($_POST['kind_traffic']); ++$i) {

            if(($_POST['kind_traffic'][$i]['name'] !== $old[$i]['name']) || ($_POST['kind_traffic'][$i]['notes'] !== $old[$i]['notes'])) {

                $name = strip_tags($_POST['kind_traffic'][$i]['name']);
                $notes = strip_tags($_POST['kind_traffic'][$i]['notes']);

                $sql = 'UPDATE `kind_traffic` SET name = :name, notes = :notes WHERE id = :id';
                $sth = $db->dbh->prepare($sql);
                $sth->bindValue(':name', $name, PDO::PARAM_STR);
                $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
                $sth->bindValue(':id', $_POST['kind_traffic'][$i]['id'], PDO::PARAM_INT);
                $sth->execute();

                // Add audit
                add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_SYSTEM, "{$_POST['kind_traffic'][$i]['name']} traffic is changed.", implode(',', $old[$i]), implode(',', $_POST['kind_traffic'][$i]));
            }
        }


        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: kind_traffic.php");
        exit;
    }

    ####### New ####### 
    if(!empty($_POST['new']) && !empty($_POST['name'])) {

        $sql = 'INSERT INTO `kind_traffic` ( `name`, `notes`)
                VALUES (:name, :notes)';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':name', $_POST['name'], PDO::PARAM_STR);
        $sth->bindValue(':notes', $_POST['notes'], PDO::PARAM_STR);
        $sth->execute();

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: kind_traffic.php");
        exit;
    }

    unset($_POST);
    header("Location: kind_traffic.php");
}
?>
