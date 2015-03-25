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

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$sysadmin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_permissions = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

if($admin_permissions || $sysadmin_permissions) {

	$db = new PDOinstance();

	if(!$sysadmin_permissions) {

		$OPERATOR_GROUPS = array(
				1 => _('cashiers'),
				2 => _('network technicians')
				);
	}


###################################################################################################
	// PAGE HEADER
###################################################################################################

	$page['title'] = 'Operators';
	$page['file'] = 'operators.php';

	require_once dirname(__FILE__).'/include/page_header.php';

#####################################################
	// Display messages
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;

	// Security key for comparison
	$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

###################################################################################################
// Set CTable variable and create dynamic html table
###################################################################################################

	// Set Table variable
	$table = new Table();
	$table->form_name = 'operators';
    $table->action = 'operator_edit.php';
    $table->method = 'get';
	$table->table_name = 'operators';
	$table->colspan = 5;
	$table->info_field1 = _('total').": ";
	$table->info_field2 = _('operators');
	$table->info_field3 = "<label class=\"info_right\"><a href=\"operator_add.php\">["._('new operator')."]</a></label>";
	$table->onclick_id = true;
	$table->th_array = array(1 => _('id'), 2 => _('alias'), 3 => _('name'), 4 => _('language'), 5 => _('group'));
	$table->th_array_style = 'style="table-layout: fixed; width: 3%"';
	$table->td_array = $Operator->get($db, null);
	echo $table->ctable();


	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>


