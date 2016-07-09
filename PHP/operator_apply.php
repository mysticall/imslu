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

$sysadmin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_permissions = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

if($admin_permissions || $sysadmin_permissions) {

	$db = new PDOinstance();

    ####### Delete ####### 
    if(!empty($_POST['delete']) && !empty($_POST['del'])) {

        $old = json_decode($_POST['old'], true);

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_OPERATOR, "Operator {$old['alias']} is deleted.", $_POST['old']);

		$Operator->delete($db, $old);

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

		header("Location: operators.php");
		exit;
    }


    ####### Edit ####### 
    if (!empty($_POST['edit'])) {

        $old = json_decode($_POST['old'], true);
		$update = array();

		//admin or system admin can change alias
		if($old['alias'] != $_POST['alias']) {

            $str = strip_tags($_POST['alias']);
            $update['alias'] = preg_replace('/\s+/', '_', $str);

			// Add audit
			add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The alias is changed.", "Old alias - {$old['alias']}", "New alias - {$update['alias']}");
        }
        if($old['name'] != $_POST['name']) {
            $update['name'] = strip_tags($_POST['name']);
        }
        if (!empty($_POST['password1'])) {

           	$password = md5($_POST['password1']);

            // Add audit
            add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The password on {$old['alias']} is changed.");
            $_SESSION['msg'] .= _s('The password on %s is changed.', $old['alias']).'<br>';

            $update['passwd'] = $password;
        }
        if($old['url'] != $_POST['url']) {
            $update['url'] = strip_tags($_POST['url']);
        }
        if($old['lang'] != $_POST['lang']) {
            $update['lang'] = $_POST['lang'];
        }
        if($old['theme'] != $_POST['theme']) {
            $update['theme'] = $_POST['theme'];
        }
        if($old['type'] != $_POST['type']) {
            $update['type'] = $_POST['type'];
        }

        // Apply changes
        $Operator->update($db, $update, $old['operid']);

        // Logout operator if ->
        if(($old['operid'] == $_SESSION['data']['operid'] && !empty($update['passwd'])) || (!empty($update['alias']) && $old['alias'] == $_SESSION['data']['alias'] && $update['alias'] != $_SESSION['data']['alias'])) {

            $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

            $db->destroy_session_handler();
            exit;
        }
        else {

            $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
          header("Location: operators.php");
            exit;
        }
    }


    ####### New #######
    if (!empty($_POST['new'])) {

        $operator = array();
        $str = strip_tags($_POST['alias']);
        $operator['alias'] = preg_replace('/\s+/', '_', $str);
        $operator['name'] = strip_tags($_POST['name']);
        $operator['passwd'] = md5($_POST['password1']);

        if(!empty($_POST['url'])) {
            $operator['url'] = strip_tags($_POST['url']);
        }

        $operator['lang'] = $_POST['lang'];
        $operator['theme'] = $_POST['theme'];
        $operator['type'] = $_POST['type'];

        // Add audit
        add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_OPERATOR, "Operator {$operator['alias']} is added.");

        $Operator->create($db, $operator);
    }

    header("Location: operators.php");
    exit;
}
?>
