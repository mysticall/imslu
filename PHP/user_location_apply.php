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

require_once dirname(__FILE__).'/include/common.inc.php';

if (!CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {
    header('Location: index.php');
    exit;
}
if ($_SESSION['form_key'] !== $_POST['form_key']) {
    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.inc.php';

//System Admin or Admin have acces to location
if((OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']) || (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type'])) {

    $db = new CPDOinstance();
    
###################################################################################################
    // Delete the location
###################################################################################################

if(isset($_POST['delete']) && isset($_POST['del'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];

    $sql = 'DELETE FROM location WHERE id = ? AND name = ? LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(1, $id);
    $sth->bindParam(2, $name);
    $sth->execute();

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
    unset($_POST);
    header("Location: user_location.php");
    exit;
}

###################################################################################################
    // Update the location
###################################################################################################

if (!empty($_POST['save_edited']) && !empty($_POST['id'])) {

    $id = $_POST['id'];

    if(empty($_POST['name'])) {

        $msg['msg_name'] = _('Name cannot empty.');
        show_error_message('id', $id, null, $msg, 'user_location.php');
    exit;
    }
        
    $name = strip_tags($_POST['name']);

    $sql = 'UPDATE `location` SET name = :name WHERE id = :id';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':name', $name);
    $sth->bindValue(':id', $id);
    $sth->execute();

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
    unset($_POST);
    header("Location: user_location.php");
    exit;
}

###################################################################################################
//Save new location
###################################################################################################

if (!empty($_POST['save_new'])) {

    if(empty($_POST['name'])) {

        $msg['msg_name'] = _('Name cannot empty.');
        show_error_message('action', 'newlocation', null, $msg, 'user_location.php');
    exit;
    }
    
    $name = strip_tags($_POST['name']);

    $sql = 'INSERT INTO `location` (`name`) VALUES (:name)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':name', $name);
    $sth->execute();

    unset($_POST);
    header("Location: user_location.php");
    exit;
}

    header("Location: user_location.php");

}