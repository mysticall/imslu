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

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();

	$_network_type = array('public' => _('public'), 'private' => _('private'));

###################################################################################################
	// PAGE HEADER
###################################################################################################

	$page['title'] = 'Static IP addresses';
	$page['file'] = 'static_ippool.php';

	require_once dirname(__FILE__).'/include/page_header.php';


	$sql = 'SELECT pool_name, network_type FROM static_ippool GROUP BY pool_name, network_type';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		for ($i = 0; $i < count($rows); ++$i) {

			$row[$rows[$i]['pool_name']] = $rows[$i]['pool_name'];
			$row2[$rows[$i]['network_type']] = $rows[$i]['network_type'];
		}
		$pool_name = array('' => '') + $row;
		$network_type = array('' => '') + $row2;
	}
	else {
		$pool_name = array('' => '');
		$network_type = array('' => '');
	}

	$order_by = array(
		'' => '',
		'network_type ASC' => _('network type')." "._('up'),
		'network_type DESC' => _('network type')." "._('down'),
		'pool_name ASC' => _('pool name')." "._('up'),
		'pool_name DESC' => _('pool name')." "._('down'),
		'id ASC' => _('id')." "._('up'),
		'id DESC' => _('id')." "._('down'),
		'name ASC' => _('name')." "._('up'),
		'name DESC' => _('name')." "._('down')
		);

    $items = array(
        '' => '',
        'addippool' => _('add IP pool')
        );

	$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">"._('name').combobox('input select', 'pool_name', null, $pool_name)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('network type').combobox('input select', 'network_type', null, $network_type)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('order by').combobox('input select', 'order_by', null, $order_by)."</label>
              <input type=\"hidden\" name=\"show\" id=\"show\">
              <label class=\"generator\" style=\"margin: 1px 5px 1px;\" onclick=\"document.getElementById('show').value = 'true'; this.form.submit()\">"._('show')."</label>
              <label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items, null) ."</label>
            </th>
          </tr>
        </tbody>
      </table>
    </form>\n";

	echo $form;

#####################################################
	// Display messages
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;

	// Security key for comparison
	$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

###################################################################################################
	// Add new SQLIPPOOL
###################################################################################################	

	if (!empty($_POST['action']) && $_POST['action'] == 'addippool') {
		
		$form =
"    <form action=\"static_ippool_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('Add new IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"pool_name\">";
		$form .= (isset($_POST['msg_pool_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_pool_name']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('start IP address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"ipaddress_start\">";
		$form .= (isset($_POST['msg_ipaddress_start'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_ipaddress_start']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('end IP address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"ipaddress_end\">";
		$form .= (isset($_POST['msg_ipaddress_end'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_ipaddress_end']}</span>\n" : "\n";
		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('network type')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'network_type', null, $_network_type)."
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"save_static_ippool\" value=\""._('save')."\">
            </td>
          </tr>		
        </tbody>
      </table>
    </form>\n";

		echo $form;
	}		

###################################################################################################
	// Set CTable variables and create dynamic html table
###################################################################################################

	if (!empty($_POST['show'])) {

        $table = new Table();
		$table->form_name = 'static_ip';
        $table->action = 'static_ippool_apply.php';
		$table->table_name = 'static_ip_addresses';
		$table->colspan = 13;
		$table->info_field1 = _('total').": ";
		$table->info_field2 = _('static IP addresses');

		$items1 = array(
			'' => '',
			'delete' => _('delete selected'),
			'change_network_type' => _('change network type'),
			'change_pool_name' => _('change pool name')
			);

		$combobox_form_submit  = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, "confirm_delete('static_ip', this[this.selectedIndex].value)") ."</label>";

		$table->info_field3 = $combobox_form_submit;
		$table->checkbox = true;
		$table->onclick_id = true;
		$table->th_array = array(
			1 => _('id'),
			2 => _('user id'),
			3 => _('shaper id'),
			4 => _('IP address'),
			5 => _('subnet'),
			6 => _('vlan'),
			7 => _('mac'),
			8 => _('mac info'),
			9 => _('free mac'),
			10 => _('name'),
			11 => _('network type'),
			12 => _('name')
			);


		$pool_name = (!empty($_POST['pool_name'])) ? $_POST['pool_name'] : '';
		$network_type = (!empty($_POST['network_type'])) ? $_POST['network_type'] : '';
		$order_by = (!empty($_POST['order_by'])) ? $_POST['order_by'] : '';

		$_pool_name = (!empty($pool_name)) ? ' AND pool_name = :pool_name' : '';
		$_network_type = (!empty($network_type)) ? ' AND network_type = :network_type' : '';
		$_order_by = (!empty($order_by)) ? " ORDER BY $order_by" : '';
		
		$sql = "SELECT id,userid,trafficid,ipaddress,subnet,vlan,mac,mac_info,free_mac,pool_name,network_type,name 
				FROM static_ippool 
				WHERE subnet = :subnet$_pool_name$_network_type$_order_by";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':subnet', 32, PDO::PARAM_INT);

		if (!empty($_pool_name)) {
			$sth->bindValue(':pool_name', $pool_name, PDO::PARAM_STR);
		}

		if (!empty($network_type)) {
			$sth->bindValue(':network_type', $network_type, PDO::PARAM_STR);
		}

		$sth->execute();
		$table->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
        $table->form_key = $_SESSION['form_key'];

		echo $table->ctable();
	}

	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
