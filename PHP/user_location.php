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

//System Admin or Admin have acces to location
if((OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']) || (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type'])) {

    $db = new CPDOinstance();
    $ctable = new CTable();


###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'The location of User';
$page['file'] = 'user_location.php';

require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;


###################################################################################################
// New location
###################################################################################################

if(isset($_POST['action']) && $_POST['action'] == 'newlocation') {

    $form =
"    <form name=\"new_location\" action=\"user_location_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('New location')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\">";
    $form .= (isset($_POST['msg_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_name']}</span>\n" : "\n";
    $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"save_new\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}

###################################################################################################
// Edit the location
###################################################################################################

if(!empty($_POST['id'])) {
	
	$id = $_POST['id'];

	$sql = 'SELECT id,name FROM location WHERE id = ? LIMIT 1';
	$sth = $db->dbh->prepare($sql);
	$sth->bindParam(1, $id, PDO::PARAM_INT);
	$sth->execute();
	$get_location = $sth->fetch(PDO::FETCH_ASSOC);

    $form =
"    <form name=\"edit_location\" action=\"user_location_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('Edit the location').": ".chars($get_location['name'])."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\" value=\"".chars($get_location['name'])."\">";
    $form .= (isset($_POST['msg_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_name']}</span>\n" : "\n";
    $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value=\"{$get_location['id']}\">
              <input type=\"submit\" name=\"save_edited\" id=\"save\" value=\""._('save')."\">
              <input type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}

###################################################################################################
// Set CTable variable and create dynamic html table
###################################################################################################

	// Set CTable variable
	$ctable->form_name = 'location';
	$ctable->table_name = 'user_location';
	$ctable->colspan = 2;
	$ctable->info_field1 = _('total').": ";
	$ctable->info_field2 = _('The location');

	$items1 = array(
		'' => '',
		'newlocation' => _('new location')
		);

	$combobox_form_submit = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, null) ."</label>";

	$ctable->info_field3 = $combobox_form_submit;
	$ctable->onclick_id = true;
	$ctable->th_array = array(
		1 => _('ID'),
		2 => _('the location')
		);

	$sql = 'SELECT id,name FROM location';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$ctable->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
	echo $ctable->ctable();

	require_once dirname(__FILE__).'/include/page_footer.php';

}
else {
	header('Location: profile.php');
}
?>
