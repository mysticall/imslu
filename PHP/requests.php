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

$db = new PDOinstance();

###################################################################################################
// PAGE HEADER
###################################################################################################

$page['title'] = 'Requests';
$page['file'] = 'requests.php';

require_once dirname(__FILE__).'/include/page_header.php';

#####################################################
    // Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

$form =
"      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
		      <label class=\"info_right\">
		      <a href=\"request_add.php\">["._('new request')."]</a>
		      <a href=\"requests.php?status=pending\">["._('pending')."]</a>
		      <a href=\"requests.php?status=connected\">["._('connected ')."]</a>
		      <a href=\"requests.php?status=refused\">["._('refused')."]</a>
              <a href=\"requests.php?status=closed\">["._('closed ')."]</a>
              </label>
            </th>
          </tr>
          <tr>
        </tbody>
      </table>\n";
echo $form;


$_GET['status'] = (!empty($_GET['status'])) ? $_GET['status'] : '';

switch($_GET['status']) {
	case "":
        // see $request_status in /include/common.inc.php
	    $status = '0 OR status = 1';
	    break;
    case "pending":
        $status = '2';
        break;
    case "connected":
        $status = '3';
        break;
    case "refused":
        $status = '4';
        break;
    case "closed":
        $status = '5';
        break;
}

// Select requests
$sql = "SELECT `requestid`, `status`, `add`, `assign`, `name`, `address`, `phone_number`, `notes` 
		FROM `requests` 
		WHERE status = $status
		ORDER BY `assign` DESC";

$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $rows[$i]['status'] = $request_status[$rows[$i]['status']];
    }

    $table = new Table();
    $table->form_name = 'requests';
    $table->action = 'request_edit.php';
    $table->table_name = 'requests';
    $table->colspan = 8;
    $table->info_field1 = _('total').": ";
    $table->info_field2 = _('requests');
    $table->onclick_id = true;
    $table->th_array = array(
            1 => _('id'),
            2 => _('status'),
            3 => _('date'),
            4 => _('assign'),
            5 => _('user'),
            6 => _('address'),
            7 => _('phone'),
            8 => _('notes')
    );

    $table->td_array = $rows;
    echo $table->ctable();
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
