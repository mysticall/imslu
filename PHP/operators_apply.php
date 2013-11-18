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

if((OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']) || (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type'])) {

	$db = new CPDOinstance();
	$coperators = new COperator();
	$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']);
	$admin_rights = (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);

###################################################################################################
	// Delete Operator
###################################################################################################
	if(!empty($_POST['delete']) && !empty($_POST['del'])) {

		$operator['operid'] = $_POST['operid'];
		$operator['alias'] = $_POST['alias'];
		$operator['type'] = $_POST['type'];

		// Add audit
		add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_OPERATOR, "Operator {$operator['alias']} is deleted.", "ID - {$operator['operid']}, Alias - {$operator['alias']}, Name - {$_POST['name']}");

		$coperators->delete($db, $operator);

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

		unset($_POST);
		header("Location: operators.php");
		exit;
	}

###################################################################################################
	// Update Operator changes
###################################################################################################

	if (!empty($_POST['save']) && !empty($_POST['operid'])) {

		$operid = $_POST['operid'];
		$operator = array();

		//Only System Admin can change alias
		if(isset($_POST['alias']) && ($_POST['alias'] != $_POST['alias_old']) && $sysadmin_rights) {

			if(empty($_POST['alias'])) {
				$_SESSION['msg'] .= _('Alias cannot empty.').'<br>';
			}
			else {

				// Add audit
				add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_OPERATOR, "The alias is changed.", "Old alias - {$_POST['alias_old']}", "New alias - {$operator['alias']}");

				$str = strip_tags($_POST['alias']);
                $operator['alias'] = preg_replace('/\s+/', '_', $str);
			}
		}
	
		if(empty($_POST['name'])) {
			$_SESSION['msg'] .= _('Name cannot empty.').'<br>';
		}
		else {
			$operator['name'] = strip_tags($_POST['name']);
		}
	
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

		if(!empty($_POST['url'])) {
			$operator['url'] = strip_tags($_POST['url']);
		}

		$operator['lang'] = $_POST['lang'];
		$operator['theme'] = $_POST['theme'];
		$operator['type'] = $_POST['type'];

		// Apply changes
		$coperators->update($db, $operator, $operid);
	
		// Logout operator if ->
		if(($operid == CWebOperator::$data['operid'] && !empty($operator['passwd'])) || ($_POST['alias_old'] == CWebOperator::$data['alias'] && $operator['alias'] != CWebOperator::$data['alias'])) {

			$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

			unset($_POST);
			echo 
'<form name="myform" method="post" action="logout.php">
	<input type="hidden" name="logout" value="logout">
  	<script language="JavaScript">document.myform.submit();</script>
</form>';

		}
		else {
		
			$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
			unset($_POST);
			header("Location: operators.php");
		}
	}

###################################################################################################
	// Save new operator
###################################################################################################

	if (!empty($_POST['savenew'])) {
	
		if(empty($_POST['alias'])) {

			$msg['msg_alias'] = _('Alias cannot empty.');
			show_error_message('action', 'addoperator', null, $msg, 'operators.php');
		exit;
		}

		$operator = array();
        $str = strip_tags($_POST['alias']);
		$operator['alias'] = preg_replace('/\s+/', '_', $str);
		$operator['name'] = strip_tags($_POST['name']);

		if (($_POST['p1'] !== $_POST['p2']) || empty($_POST['p1'])) {
			
			$msg['msg_password'] = (empty($_POST['p1']) || empty($_POST['p1'])) ? _('Please enter a password.') :_('Both passwords must be equal.');
			show_error_message('action', 'addoperator', null, $msg, 'operators.php');
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

			$coperators->create($db, $operator);
		}	
	}

	header("Location: operators.php");
}
?>