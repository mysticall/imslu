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

###################################################################################################
	// Delete Operator
###################################################################################################
	if(!empty($_POST['delete']) && !empty($_POST['del'])) {

		$operator['operid'] = $_POST['operid'];
		$operator['alias'] = $_POST['alias'];
		$operator['type'] = $_POST['type'];

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_OPERATOR, "Operator {$operator['alias']} is deleted.", "ID - {$operator['operid']}, Alias - {$operator['alias']}, Name - {$_POST['name']}");

		$Operator->delete($db, $operator);

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

		header("Location: operators.php");
		exit;
	}

###################################################################################################
	// Update Operator changes
###################################################################################################

	if (!empty($_POST['edit']) && !empty($_POST['operid'])) {

		$operid = $_POST['operid'];
		$operator = array();

		//admin or system admin can change alias
		if($_POST['alias'] != $_POST['alias_old']) {

			if(empty($_POST['alias'])) {

				$_SESSION['msg'] .= _('Alias cannot empty.').'<br>';
                header("Location: operator_edit.php?operid={$operid}");
                exit;
			}
			else {

                $str = strip_tags($_POST['alias']);
                $operator['alias'] = preg_replace('/\s+/', '_', $str);

				// Add audit
				add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The alias is changed.", "Old alias - {$_POST['alias_old']}", "New alias - {$operator['alias']}");
			}
		}

		$operator['name'] = strip_tags($_POST['name']);
	
		if (!empty($_POST['p1']) || !empty($_POST['p2'])) {

			if ($_POST['p1'] !== $_POST['p2']) {

				$_SESSION['msg'] .= _('Both passwords must be equal.').'<br>';
			}
			elseif ($_POST['p1'] === $_POST['p2']) {

				$password = $_POST['p1'];
				$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
				$password1 = hash('sha512', $password.$random_salt);

				// Add audit
				add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The password on {$_POST['alias_old']} is changed.");
				$_SESSION['msg'] .= _s('The password on %s is changed.', chars($_POST['alias_old'])).'<br>';

				$operator['passwd'] = $password1;
				$operator['salt'] = $random_salt;
			}
		}

		$operator['url'] = strip_tags($_POST['url']);
		$operator['lang'] = $_POST['lang'];
		$operator['theme'] = $_POST['theme'];
		$operator['type'] = $_POST['type'];

		// Apply changes
		$Operator->update($db, $operator, $operid);
	
		// Logout operator if ->
		if(($operid == $_SESSION['data']['operid'] && !empty($operator['passwd'])) || ($_POST['alias_old'] == $_SESSION['data']['alias'] && $operator['alias'] != $_SESSION['data']['alias'])) {

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

###################################################################################################
	// Save new operator
###################################################################################################

	if (!empty($_POST['new'])) {
	
		if(empty($_POST['alias'])) {

            $_SESSION['msg'] .= _('Alias cannot empty.').'<br>';
            header("Location: operator_add.php");
            exit;
		}

		$operator = array();
        $str = strip_tags($_POST['alias']);
		$operator['alias'] = preg_replace('/\s+/', '_', $str);
		$operator['name'] = strip_tags($_POST['name']);

		if (($_POST['p1'] !== $_POST['p2']) || empty($_POST['p1'])) {
			
			$_SESSION['msg'] .= (empty($_POST['p1']) || empty($_POST['p1'])) ? _('Please enter a password.') :_('Both passwords must be equal.');
            header("Location: operator_add.php");
            exit;
		}

		if ($_POST['p1'] === $_POST['p2']) {

			$password = $_POST['p1'];
			$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
			$password1 = hash('sha512', $password.$random_salt);

			$operator['passwd'] = $password1;
			$operator['salt'] = $random_salt;

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
	}

	header("Location: operators.php");
    exit;
}
?>
