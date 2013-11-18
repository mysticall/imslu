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

$db = new CPDOinstance();
$admin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type'] || OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);
$disabled = ($admin_rights) ? '' : ' disabled';


###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'New User';
$page['file'] = 'user_new.php';

require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


// Security key for comparison
$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

###################################################################################################
	// Add new User
###################################################################################################	

#####################################################
	// Get avalible tariff plans
#####################################################
$sql = 'SELECT trafficid,name,price FROM traffic';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
	for ($i = 0; $i < count($rows); ++$i) {

		$tariff_plan[$rows[$i]['trafficid']] = $rows[$i]['name'] .' - '. $rows[$i]['price'];
	}
}
else {
	
	echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'.
		_('Please contact your system administrator. Not created tariff plan in the "Traffic control"') .'<label>';

	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}

#####################################################
	// Get avalible IP addresses
#####################################################
$sql = 'SELECT ipaddress FROM static_ippool WHERE userid = :userid';
$sth = $db->dbh->prepare($sql);
$sth->bindValue(':userid', '0');
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
	for ($i = 0; $i < count($rows); ++$i) {

		$ip[$rows[$i]['ipaddress']] = $rows[$i]['ipaddress'];
	}
	$ip_addresses = array('' => '') + $ip;
}
else {
	
	echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'. 
		_('Please contact your system administrator. Not added static IP addresses in the "Static IP addresses"') .'<label>';

	require_once dirname(__FILE__).'/include/page_footer.php';
	exit;
}

#####################################################
	// Get avalible Freeradius Groups
#####################################################
//Check available Freeradius Groups if $USE_PPPoE == True
if ($USE_PPPoE) {
		
	$sql = 'SELECT groupname FROM radgroupcheck GROUP BY groupname';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {

		for ($i = 0; $i < count($rows); ++$i) {

			$fr_groupname[$rows[$i]['groupname']] = $rows[$i]['groupname'];
		}
	}
	else {
	
		echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'. 
			_('Please contact your system administrator. Not created FreeRADIUS group in the "Groups"') .'<label>';

		require_once dirname(__FILE__).'/include/page_footer.php';
		exit;
	}
}

#####################################################
	// Get avalible locations
#####################################################	
$sql = 'SELECT id,name FROM location';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
	for ($i = 0; $i < count($rows); ++$i) {

		$location_name[$rows[$i]['id']] = $rows[$i]['name'];
	}
	$location = array('' => '') + $location_name;
}
else {
	$location = array('' => '');
}

#####################################################
	// Get avalible switches
#####################################################
$sql = 'SELECT id,name FROM switches';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
	for ($i = 0; $i < count($rows); ++$i) {

		$switche_name[$rows[$i]['id']] = $rows[$i]['name'];
	}
	$switches = array('' => '') + $switche_name;
}
else {
	$switches = array('' => '');
}

$use_pppoe = array('' => '', 'PPPoE' => _('yes'));

$form =
"    <form name=\"new_user\" action=\"user_new_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('New user')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('First name Surname')." * </label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"user_name\" size=\"35\">";
$form .= (isset($_POST['msg_user_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_user_name']}</span>\n" : "\n";
$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('The location')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'locationid', null, $location)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Switch')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'switchid', null, $switches)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"address\" size=\"35\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Phone number')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"phone_number\" size=\"35\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Notes')."</label>
            </td>
			<td class=\"dd\">
              <textarea name=\"notes\" cols=\"55\" rows=\"3\"></textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Tariff plan')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'trafficid', null, $tariff_plan)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Pay')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"pay\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Free internet access')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"free_access\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Not excluding')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"not_excluding\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('IP address')." * </label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'ip', null, $ip_addresses);
$form .= (isset($_POST['msg_ip'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_ip']}</span>\n" : "\n";
$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('VLAN')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"vlan\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Free MAC')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"free_mac\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('MAC')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"mac\">
            </td>
          </tr>\n";

#####################################################
// PPPoe - Freeradius, $USE_PPPoE must be TRUE
#####################################################

if (!empty($fr_groupname)) {

    $form .=
"          <tr>
            <td class=\"dt right\">
              <label>"._('Use PPPoE')." * </label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'use_pppoe', $use_pppoe, 'add_pppoe(\'tbody\', this[this.selectedIndex].value)');
$form .= (isset($_POST['msg_use_pppoe'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_use_pppoe']}</span>\n" : "\n";
$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('FreeRADIUS group')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'groupname', null, $fr_groupname)."
            </td>
          </tr>\n";
}

$form .=
"        </tbody>
        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"savenew\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot>
      </table>
    </form>\n";

echo $form;

require_once dirname(__FILE__).'/include/page_footer.php';
?>
