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

$sysadmin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_permissions = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

$db = new PDOinstance();

####### Edit ####### 
if (!empty($_POST['edit'])) {

    $update = array();

    //admin or system admin can change alias
    if($admin_permissions || $sysadmin_permissions) {

        if($_SESSION['data']['alias'] != $_POST['alias']) {

            // Add audit
            add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The alias is changed.", "Old alias - {$_SESSION['data']['alias']}", "New alias - {$_POST['alias']}");

            $str = strip_tags($_POST['alias']);
            $update['alias'] = preg_replace('/\s+/', '_', $str);
        }
    }
    if($_SESSION['data']['name'] != $_POST['name']) {

        $update['name'] = strip_tags($_POST['name']);
        $_SESSION['data']['name'] = $update['name'];
    }
    if (!empty($_POST['password1'])) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The password on {$_SESSION['data']['alias']} is changed.");
        $_SESSION['msg'] .= _s('The password of %s is changed.', $_SESSION['data']['alias']).'<br>';

        $update['passwd'] = md5($_POST['password1']);
    }
    if($_SESSION['data']['url'] != $_POST['url']) {

        $update['url'] = strip_tags($_POST['url']);
        $_SESSION['data']['url'] = $update['url'];
    }
    if($_SESSION['data']['lang'] !=  $_POST['lang']) {

        $update['lang'] = $_POST['lang'];
        $_SESSION['data']['lang'] = $_POST['lang'];
        setcookie( 'lang', $_POST['lang'], time() + (86400 * 7), "/"); // 86400 = 1 day
    }
    if($_SESSION['data']['theme'] != $_POST['theme']) {

        $update['theme'] = $_POST['theme'];
        $_SESSION['data']['theme'] = $_POST['theme'];
        setcookie( 'theme', $_POST['theme'], time() + (86400 * 7), "/"); // 86400 = 1 day
    }

    if (!empty($update)) {

        $i= 1;
        foreach($update as $key => $value) {
            $keys[$i] = $key;
            $values[$i] = $value;

        $i++;
        }

        $sql = 'UPDATE `operators` SET '.implode(' = ?, ', $keys).' = ? WHERE `operid` = ?';

        // Apply changes
        array_push($values, $_SESSION['data']['operid']);
        $db->prepare_array($sql, $values);
    }

    // Logout operator if ->
    if(!empty($update['passwd']) || !empty($update['alias'])) {

        $db->destroy_session_handler();
    }
    else {

        header('Location: profile.php');
        exit;
    }
}
header('Location: profile.php');

?>
