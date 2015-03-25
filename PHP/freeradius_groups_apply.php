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

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

    $db = new PDOinstance();


###################################################################################################
    // Delete Freeradius Group
###################################################################################################
if(!empty($_POST['delete']) && !empty($_POST['del'])) {

    $group = $_POST['group'];

    $sql = 'DELETE FROM `radgroupcheck` WHERE groupname = :groupname';
    $db->dbh->beginTransaction();
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':groupname', $group);
    $sth->execute();

    $sql2 = 'DELETE FROM `radgroupreply` WHERE groupname = :groupname';
    $sth = $db->dbh->prepare($sql2);
    $sth->bindParam(':groupname', $group);
    $sth->execute();

    $db->dbh->commit();

    // Add audit
    add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_FREERADIUS, "Group {$_POST['group']} is deleted.", "Group name - {$_POST['group']}");
    unset($_POST);
    header("Location: freeradius_groups.php");
    exit;
}

###################################################################################################
    // Save changes
###################################################################################################

if (!empty($_POST['change_group'])) {
    
    $group = $_POST['group'];
    $group_radgroupcheck = unserialize($_POST['group_radgroupcheck']);
    $group_radgroupreply = unserialize($_POST['group_radgroupreply']);

    // Radgroupcheck
    $count_radgroupcheck = count($group_radgroupcheck);
    settype($count_radgroupcheck, "integer");
    $update = 0;
    $insert = 0;
    $delete = 0;
    
    for ($i = 0; $i <= $count_radgroupcheck; ++$i) {

        $attribute_radgroupcheck = isset($group_radgroupcheck[$i]['attribute']) ? $group_radgroupcheck[$i]['attribute'] : '';

        foreach ($_POST['radgroupcheck'] as $key => $value) {

            // If attritube value is not empty and exist on DB add to UPDATE query array()
            if ($attribute_radgroupcheck === $key && $value !== "") {

                $update_radgroupcheck[$update]['attribute'] = $key;
                $update_radgroupcheck[$update]['op'] = $_POST['op'][$key];
                $update_radgroupcheck[$update]['value'] = strip_tags($value);

                unset($_POST['radgroupcheck'][$key], $_POST['op'][$key]);

            ++$update;
            }

            // If attritube exist on DB and value is empty add to DELETE query array()
            if ($attribute_radgroupcheck === $key && $value === "") {
            
                $delete_radgroupcheck[$delete]['attribute'] = $key;

                unset($_POST['radgroupcheck'][$key], $_POST['op'][$key]);

            ++$delete;
            }
        }
    }

    foreach ($_POST['radgroupcheck'] as $key => $value) {
        
        // If attritube not exist on DB and value is not empty add to INSERT query array()
        if ($value !== "") {
            
            $insert_radgroupcheck[$insert]['attribute'] = $key;
            $insert_radgroupcheck[$insert]['op'] = $_POST['op'][$key];
            $insert_radgroupcheck[$insert]['value'] = strip_tags($value);

            unset($_POST['radgroupcheck'][$key], $_POST['op'][$key]);

        ++$insert;
        }
    }
    
    
    // Radgroupreply
    $count_radgroupreply = count($group_radgroupreply);
    settype($count_radgroupreply, "integer");
    $update = 0;
    $insert = 0;
    $delete = 0;
    
    for ($i = 0; $i <= $count_radgroupreply; ++$i) {

        $attribute_radgroupreply = isset($group_radgroupreply[$i]['attribute']) ? $group_radgroupreply[$i]['attribute'] : '';

        foreach ($_POST['radgroupreply'] as $key => $value) {

            // If attritube value is not empty and exist on DB add to UPDATE query array()
            if ($attribute_radgroupreply === $key && $value !== "") {

                $update_radgroupreply[$update]['attribute'] = $key;
                $update_radgroupreply[$update]['op'] = $_POST['op'][$key];
                $update_radgroupreply[$update]['value'] = strip_tags($value);

                unset($_POST['radgroupreply'][$key], $_POST['op'][$key]);

            ++$update;
            }

            // If attritube exist on DB and value is empty add to DELETE query array()
            if ($attribute_radgroupreply === $key && $value === "") {
            
                $delete_radgroupreply[$delete]['attribute'] = $key;

                unset($_POST['radgroupreply'][$key], $_POST['op'][$key]);

            ++$delete;
            }
        }
    }

    foreach ($_POST['radgroupreply'] as $key => $value) {
        
        // If attritube not exist on DB and value is not empty add to INSERT query array()
        if ($value !== "") {
            
            $insert_radgroupreply[$insert]['attribute'] = $key;
            $insert_radgroupreply[$insert]['op'] = $_POST['op'][$key];
            $insert_radgroupreply[$insert]['value'] = strip_tags($value);

            unset($_POST['radgroupreply'][$key], $_POST['op'][$key]);

        ++$insert;
        }
    }


    if (isset($update_radgroupcheck[0]['attribute'])) {
        
        // UPDATE radgroupcheck
        $sql = 'UPDATE `radgroupcheck` SET op = :op, value = :value WHERE groupname = :groupname AND attribute = :attribute';
        $db->dbh->beginTransaction();
        $sth = $db->dbh->prepare($sql);
        $sth->bindParam(':op', $op);
        $sth->bindParam(':value', $value);
        $sth->bindParam(':groupname', $group);
        $sth->bindParam(':attribute', $attribute);
        
        for ($i = 0; $i < count($update_radgroupcheck); ++$i) {
    
            $op = $update_radgroupcheck[$i]['op'];
            $value = $update_radgroupcheck[$i]['value'];
            $attribute = $update_radgroupcheck[$i]['attribute'];

            $sth->execute();
        }
    }

    if (isset($update_radgroupreply[0]['attribute'])) {
        
        // UPDATE radgroupreply
        $sql2 = 'UPDATE `radgroupreply` SET op = :op, value = :value WHERE groupname = :groupname AND attribute = :attribute';
        $sth = $db->dbh->prepare($sql2);
        $sth->bindParam(':op', $op);
        $sth->bindParam(':value', $value);
        $sth->bindParam(':groupname', $group);
        $sth->bindParam(':attribute', $attribute); 

        for ($i = 0; $i < count($update_radgroupreply); ++$i) {
    
            $op = $update_radgroupreply[$i]['op'];
            $value = $update_radgroupreply[$i]['value'];
            $attribute = $update_radgroupreply[$i]['attribute'];

            $sth->execute();
        }
    }
    
    if (isset($insert_radgroupcheck[0]['attribute'])) {

        // INSERT INTO radgroupcheck
        $sql3 = "INSERT INTO `radgroupcheck` (`groupname`,`attribute`,`op`,`value`) VALUES (:groupname, :attribute, :op, :value)";
        $sth = $db->dbh->prepare($sql3);
        $sth->bindParam(':groupname', $group);
        $sth->bindParam(':attribute', $attribute);
        $sth->bindParam(':op', $op);
        $sth->bindParam(':value', $value);

        for ($i = 0; $i < count($insert_radgroupcheck); ++$i) {

            $attribute = $insert_radgroupcheck[$i]['attribute'];
            $op = $insert_radgroupcheck[$i]['op'];
            $value = $insert_radgroupcheck[$i]['value'];

        $sth->execute();
        }
    }

    if (isset($insert_radgroupreply[0]['attribute'])) {
        
        // INSERT INTO radgroupreply
        $sql4 = "INSERT INTO `radgroupreply` (`groupname`,`attribute`,`op`,`value`) VALUES (:groupname, :attribute, :op, :value)";
        $sth = $db->dbh->prepare($sql4);
        $sth->bindParam(':groupname', $group);
        $sth->bindParam(':attribute', $attribute);
        $sth->bindParam(':op', $op);
        $sth->bindParam(':value', $value);

        for ($i = 0; $i < count($insert_radgroupreply); ++$i) {

            $attribute = $insert_radgroupreply[$i]['attribute'];
            $op = $insert_radgroupreply[$i]['op'];
            $value = $insert_radgroupreply[$i]['value'];

        $sth->execute();
        }
    }

    if (isset($delete_radgroupcheck[0]['attribute'])) {
        
        // DELETE FROM radgroupcheck
        $sql5 = "DELETE FROM `radgroupcheck` WHERE groupname = :groupname AND attribute = :attribute";
        $sth = $db->dbh->prepare($sql5);
        $sth->bindParam(':groupname', $group);
        $sth->bindParam(':attribute', $attribute);
        
        for ($i = 0; $i < count($delete_radgroupcheck); ++$i) {

            $attribute = $delete_radgroupcheck[$i]['attribute'];

        $sth->execute();
        }
    }

    if (isset($delete_radgroupreply[0]['attribute'])) {
        
        // DELETE FROM radgroupreply
        $sql6 = "DELETE FROM `radgroupreply` WHERE groupname = :groupname AND attribute = :attribute";
        $sth = $db->dbh->prepare($sql6);
        $sth->bindParam(':groupname', $group);
        $sth->bindParam(':attribute', $attribute);
        
        for ($i = 0; $i < count($delete_radgroupreply); ++$i) {

            $attribute = $delete_radgroupreply[$i]['attribute'];

        $sth->execute();
        }
    }
            
    $db->dbh->commit();
    
    // Add audit
    add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_FREERADIUS, "Group {$_POST['group']} is changed.");

    $_SESSION['msg'] = _('Changes are applied successfully.');
    unset($_POST);
    header("Location: freeradius_groups.php");
    exit;
}

###################################################################################################
    // Save new freeradius group
###################################################################################################
if (!empty($_POST['add_new_group'])) {

    if(empty($_POST['new_groupname'])) {

        $msg['msg_groupname'] = _('Name cannot empty.');
        show_error_message('action', 'addgroup', null, $msg, 'freeradius_groups.php');
    exit;
    }
    
    $check = 0;
    $reply = 0;

    foreach ($_POST['radgroupcheck'] as $key => $value) {
        
        // If attritube value is not empty add to INSERT query array()
        if ($value !== "") {
            
            $insert_radgroupcheck[$check]['attribute'] = $key;
            $insert_radgroupcheck[$check]['op'] = $_POST['op'][$key];
            $insert_radgroupcheck[$check]['value'] = strip_tags($value);

            unset($_POST['radgroupcheck'][$key], $_POST['op'][$key]);

        ++$check;
        }
    }

    foreach ($_POST['radgroupreply'] as $key => $value) {
        
        // If attritube value is not empty add to INSERT query array()
        if ($value !== "") {
            
            $insert_radgroupreply[$reply]['attribute'] = $key;
            $insert_radgroupreply[$reply]['op'] = $_POST['op'][$key];
            $insert_radgroupreply[$reply]['value'] = strip_tags($value);

            unset($_POST['radgroupreply'][$key], $_POST['op'][$key]);

        ++$reply;
        }
    }

    $str = strip_tags($_POST['new_groupname']);
    $groupname = preg_replace('/\s+/', '_', $str);

    // INSERT INTO radgroupcheck
    $sql = "INSERT INTO `radgroupcheck` (`groupname`,`attribute`,`op`,`value`) VALUES (:groupname, :attribute, :op, :value)";
    $db->dbh->beginTransaction();
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':groupname', $groupname);
    $sth->bindParam(':attribute', $attribute);
    $sth->bindParam(':op', $op);
    $sth->bindParam(':value', $value);

    for ($i = 0; $i < count($insert_radgroupcheck); ++$i) {

        $attribute = $insert_radgroupcheck[$i]['attribute'];
        $op = $insert_radgroupcheck[$i]['op'];
        $value = $insert_radgroupcheck[$i]['value'];

    $sth->execute();
    }

    // INSERT INTO radgroupreply
    $sql2 = "INSERT INTO `radgroupreply` (`groupname`,`attribute`,`op`,`value`) VALUES (:groupname, :attribute, :op, :value)";
    $sth = $db->dbh->prepare($sql2);
    $sth->bindParam(':groupname', $groupname);
    $sth->bindParam(':attribute', $attribute);
    $sth->bindParam(':op', $op);
    $sth->bindParam(':value', $value);

    for ($i = 0; $i < count($insert_radgroupreply); ++$i) {

        $attribute = $insert_radgroupreply[$i]['attribute'];
        $op = $insert_radgroupreply[$i]['op'];
        $value = $insert_radgroupreply[$i]['value'];

    $sth->execute();
    }

    $db->dbh->commit();

    // Add audit
    add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_FREERADIUS, "Group {$_POST['new_groupname']} is added.");

    $_SESSION['msg'] = _('The new group is added successfully.');
    unset($_POST);
    header("Location: freeradius_groups.php");
    exit;
}   

    header("Location: freeradius_groups.php");
}
?>
