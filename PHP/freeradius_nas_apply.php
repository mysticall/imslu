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

####### Delete ####### 
if(!empty($_POST['delete']) && !empty($_POST['del'])) {

    $nas_old = unserialize($_POST['nas']);

    $sql = 'DELETE FROM nas WHERE id = ? AND nasname = ? LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(1, $nas_old['id'], PDO::PARAM_INT);
    $sth->bindValue(2, $nas_old['nasname'], PDO::PARAM_STR);
    $sth->execute();

    // Add audit
    add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_FREERADIUS, "NAS {$_POST['nasname']} is deleted.", json_encode($nas_old));

    $_SESSION['msg'] = _('Changes are applied successfully.');
    unset($_POST);
    header("Location: freeradius_nas.php");
    exit;
}

####### Edit ####### 
if (!empty($_POST['edit']) && !empty($_POST['nas'])) {

    $nas_old = unserialize($_POST['nas']);

    if($nas_old['nasname'] != $_POST['nasname']) {

        $str = strip_tags($_POST['nasname']);
        $nas['nasname'] = preg_replace('/\s+/', '_', $str);
    }

    if($nas_old['shortname'] != $_POST['shortname']) {

        $str = strip_tags($_POST['shortname']);
        $nas['shortname'] = preg_replace('/\s+/', '_', $str);
    }

    if($nas_old['type'] != $_POST['type']) {

        $nas['type'] = $_POST['type'];
    }

    if($nas_old['ports'] != $_POST['ports']) {

        $nas['ports'] = $_POST['ports'];
    }

    if($nas_old['secret'] != $_POST['secret']) {

        $nas['secret'] = strip_tags($_POST['secret']);
    }

    if($nas_old['server'] != $_POST['server']) {

        $nas['server'] = strip_tags($_POST['server']);
    }   

    if($nas_old['community'] != $_POST['community']) {

        $nas['community'] = strip_tags($_POST['community']);
    }

    if($nas_old['description'] != $_POST['description']) {

        $nas['description'] = strip_tags($_POST['description']);
    }

    if(!empty($nas)) {
        
        $i= 1;
        foreach ($nas as $key => $value) {
            $keys[$i] = $key;
            $values[$i] = $value;

        $i++;
        }

        $sql = 'UPDATE nas SET '.implode(' = ?, ', $keys).' = ? WHERE id = ?';

        array_push($values, $nas_old['id']);
        $db->prepare_array($sql, $values);

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_FREERADIUS, "NAS {$_POST['nasname']} is changed.", json_encode($nas_old));

        $_SESSION['msg'] = _('Changes are applied successfully.');
        unset($_POST);
        header("Location: freeradius_nas.php");
        exit;
    }
}

####### New ####### 
if (isset($_POST['new'])) {
    
    $nas = array();

    $str = strip_tags($_POST['nasname']);
    $nas['nasname'] = preg_replace('/\s+/', '_', $str);

    $str = strip_tags($_POST['shortname']);
    $nas['shortname'] = preg_replace('/\s+/', '_', $str);

    $nas['type'] = $_POST['type'];

    if(!empty($_POST['ports'])) {
        $nas['ports'] = $_POST['ports'];
    }
    if(!empty($_POST['secret'])) {
        $nas['secret'] = strip_tags($_POST['secret']);
    }
    if(!empty($_POST['server'])) {
        $nas['server'] = strip_tags($_POST['server']);
    }   
    if(!empty($_POST['community'])) {
        $nas['community'] = strip_tags($_POST['community']);
    }
    if(!empty($_POST['description'])) {
        $nas['description'] = strip_tags($_POST['description']);
    }

    if(!empty($nas['nasname'])) {

        $i= 1;
        foreach($nas as $key => $value) {
            $keys[$i] = $key;
            $values[$i] = $value;
            $question_mark[$i] = '?';

        $i++;
        }
                            
        $sql = 'INSERT INTO `nas` (`'.implode('`,`', $keys).'`)
                VALUES ('.implode(', ', $question_mark).')';

        $db->prepare_array($sql, $values);
                
        // Add audit
        add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_FREERADIUS, "NAS {$nas['nasname']} is added.");

        $_SESSION['msg'] = _('The new NAS is added successfully.');
        unset($_POST);
        header("Location: freeradius_nas.php");
        exit;
    }   
}

    header("Location: freeradius_nas.php");
}
?>
