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

# Must be included after session check
require_once dirname(__FILE__).'/include/config.inc.php';

// logout
if (isset($_POST['logout'])) {
	
	$db = new CPDOinstance();
	$operator = CWebOperator::$data['alias'];

	// Add audit
	add_audit($db, AUDIT_ACTION_LOGOUT, AUDIT_RESOURCE_SYSTEM, "$operator logout from the system.");
	$db->destroy_session_handler();
}
else {

    $page['title'] = 'Logout';
    $page['file'] = 'logout.php';

    require_once dirname(__FILE__).'/include/page_header.php';

    echo 
"<form name=\"myform\" method=\"post\" action=\"logout.php\">
  <input type=\"hidden\" name=\"logout\" value=\"logout\">
  <script language=\"JavaScript\">document.myform.submit();</script>
</form>";

    require_once dirname(__FILE__).'/include/page_footer.php';
}
?>
