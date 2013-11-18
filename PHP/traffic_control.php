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

	$page['title'] = 'Traffic control';
	$page['file'] = 'traffic_control.php';

	require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;

	
    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));


###################################################################################################
// New location
###################################################################################################

	if(isset($_POST['action']) && $_POST['action'] == 'new_tariff_plan') {
	
		$form =
"    <form name=\"new_tariff_plan\" action=\"traffic_control_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('New tariff plan')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Tariff plan name')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\" size=\"35\" maxlength=\"255\">";
		$form .= (isset($_POST['msg_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_name']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Price')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"price\">";
		$form .= (isset($_POST['msg_price'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_price']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Local IN')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"local_in\">
              <label style=\"font-weight: bold;\"> Kbit </label>";
		$form .= (isset($_POST['msg_local_in'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_local_in']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Local OUT')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"local_out\">
              <label style=\"font-weight: bold;\"> Kbit </label>";
		$form .= (isset($_POST['msg_local_out'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_local_out']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('International IN')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"int_in\" disabled>
              <label style=\"font-weight: bold;\"> Kbit </label>";
		$form .= (isset($_POST['msg_int_in'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_int_in']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('International OUT')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name\"int_out\" disabled>
              <label style=\"font-weight: bold;\"> Kbit </label>";
        $form .= (isset($_POST['msg_int_out'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_int_out']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
              <span class=\"red\">1Mbit = 1024Kbit</span>
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

    if(!empty($_POST['trafficid'])) {
	
	   $id = $_POST['trafficid'];

	   $sql = 'SELECT * FROM traffic WHERE trafficid = ? LIMIT 1';
	   $sth = $db->dbh->prepare($sql);
	   $sth->bindParam(1, $id, PDO::PARAM_INT);
	   $sth->execute();
	   $get_tariff_plan = $sth->fetch(PDO::FETCH_ASSOC);

        $form =
"    <form name=\"new_tariff_plan\" action=\"traffic_control_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('Edit tariff plan').": ".chars($get_tariff_plan['name'])."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Tariff plan name')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\" size=\"35\" maxlength=\"255\" value=\"".chars($get_tariff_plan['name'])."\">";
        $form .= (isset($_POST['msg_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_name']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Price')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"price\" value=\"{$get_tariff_plan['price']}\">";
        $form .= (isset($_POST['msg_price'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_price']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Local IN')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"local_in\" value=\"{$get_tariff_plan['local_in']}\">
              <label style=\"font-weight: bold;\"> Kbit </label>";
        $form .= (isset($_POST['msg_local_in'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_local_in']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Local OUT')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"local_out\" value=\"{$get_tariff_plan['local_out']}\">
              <label style=\"font-weight: bold;\"> Kbit </label>";
        $form .= (isset($_POST['msg_local_out'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_local_out']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('International IN')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"int_in\" value=\"{$get_tariff_plan['int_in']}\" disabled>
              <label style=\"font-weight: bold;\"> Kbit </label>";
        $form .= (isset($_POST['msg_int_in'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_int_in']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('International OUT')." *</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"int_out\" value=\"{$get_tariff_plan['int_out']}\" disabled>
              <label style=\"font-weight: bold;\"> Kbit </label>";
        $form .= (isset($_POST['msg_int_out'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_int_out']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
              <span class=\"red\">1Mbit = 1024Kbit</span>
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"trafficid\" value=\"{$get_tariff_plan['trafficid']}\">
              <input type=\"submit\" name=\"save_edited\" id=\"save\" value=\""._('save')."\">
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
	$ctable->form_name = 'traffic_control';
	$ctable->table_name = 'traffic_control';
	$ctable->colspan = 7;
	$ctable->info_field1 = _('total').": ";
	$ctable->info_field2 = _('Traffic control - Price');

	$items1 = array(
		'' => '',
		'new_tariff_plan' => _('new tariff plan')
		);

	$combobox_form_submit = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, null) ."</label>";

	$ctable->info_field3 = $combobox_form_submit;
	$ctable->onclick_id = true;
	$ctable->th_array = array(
		1 => _('ID'),
		2 => _('name'),
		3 => _('price'),
		4 => _('local IN'),
		5 => _('local OUT'),
		6 => _('international IN'),
		7 => _('international OUT')
		);

	$sql = 'SELECT * FROM traffic';
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
